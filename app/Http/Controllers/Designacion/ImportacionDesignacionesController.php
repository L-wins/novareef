<?php

declare(strict_types=1);

namespace App\Http\Controllers\Designacion;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Designacion\ProcesarImportacionWordRequest;
use App\Models\DivisionTorneo;
use App\Models\FormatoDesignacion;
use App\Models\SedeTorneo;
use App\Models\Torneo;
use App\Services\Importacion\ImportacionPartidosService;
use App\Services\Importacion\MatchingTextoService;
use App\Services\Importacion\PartidoWordParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Importa partidos desde el .docx que las asociaciones envían a los
 * colegios (formato consistente: bloque GRUPO/CATEGORÍA/FECHA en rojo +
 * tabla de 4 filas por partido). El resultado del parseo se guarda en
 * sesión (no en una tabla de staging) — el ciclo de vida es corto: subir,
 * revisar/corregir en el preview, confirmar o cancelar, todo en la misma
 * sesión de trabajo. Ver docs/plan-importador-word-designaciones.md.
 */
class ImportacionDesignacionesController extends Controller
{
    use ResuelveColegio;

    private const CLAVE_SESION = 'importacion_designaciones';

    public function __construct(
        private readonly PartidoWordParser $parser,
        private readonly MatchingTextoService $matcher,
        private readonly ImportacionPartidosService $importador,
    ) {}

    public function mostrar(): View
    {
        $idColegio = $this->idColegioActivo();
        $datos     = session(self::CLAVE_SESION);

        if (is_array($datos) && (int) $datos['idColegio'] === $idColegio) {
            return view('designaciones.importar', [
                'importacion' => $datos,
                'divisiones'  => DivisionTorneo::where('idTorneo', $datos['idTorneo'])->orderBy('nombreDivision')->get(),
                'sedes'       => SedeTorneo::where('idTorneo', $datos['idTorneo'])->orderBy('nombreSede')->get(),
                'formatos'    => FormatoDesignacion::activos()->get(),
            ]);
        }

        $torneos = Torneo::where('idColegio', $idColegio)
            ->whereIn('estadoTorneo', ['activo', 'proximo'])
            ->orderByDesc('temporada')
            ->limit(100)
            ->get();

        $formatos = FormatoDesignacion::activos()->get();

        return view('designaciones.importar', ['importacion' => null, 'torneos' => $torneos, 'formatos' => $formatos]);
    }

    public function procesar(ProcesarImportacionWordRequest $request): RedirectResponse
    {
        $idColegio = $this->idColegioActivo();
        $datos     = $request->validated();

        $torneo = Torneo::where('idTorneo', $datos['idTorneo'])
            ->where('idColegio', $idColegio)
            ->firstOrFail();

        $rutaTemp = $request->file('archivoWord')->store('importaciones-temp', 'local');

        try {
            $crudos = $this->parser->parsear(
                Storage::disk('local')->path($rutaTemp),
                (int) $torneo->temporada,
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with(
                'error',
                'No se pudo leer el archivo — asegúrate de que sea un .docx válido y no esté dañado.',
            );
        } finally {
            Storage::disk('local')->delete($rutaTemp);
        }

        $divisiones = $this->matcher->divisionesDelTorneo($torneo->idTorneo);
        $sedes      = $this->matcher->sedesDelTorneo($torneo->idTorneo);

        $partidos = array_map(
            fn (array $crudo) => $this->construirFila($crudo, $divisiones, $sedes, (int) $datos['idFormato']),
            $crudos,
        );

        session([self::CLAVE_SESION => [
            'idTorneo'              => $torneo->idTorneo,
            'idColegio'             => $idColegio,
            'nombreArchivoOriginal' => $request->file('archivoWord')->getClientOriginalName(),
            'idFormatoDefault'      => (int) $datos['idFormato'],
            'partidos'              => $partidos,
        ]]);

        return redirect()->route('designaciones.importar.mostrar');
    }

    public function revisar(Request $request): RedirectResponse
    {
        $sesion = $this->sesionActiva();

        $sesion['partidos'] = $this->aplicarEdiciones($sesion['partidos'], $request->input('filas', []));
        session([self::CLAVE_SESION => $sesion]);

        return redirect()->route('designaciones.importar.mostrar');
    }

    /**
     * Aplica las mismas correcciones que "Guardar correcciones" antes de
     * importar — el botón "Confirmar" del preview envía las mismas filas,
     * así que un cambio hecho justo antes de confirmar (sin pasar primero
     * por "Guardar correcciones") no se pierde.
     */
    public function confirmar(Request $request): RedirectResponse
    {
        $idColegio = $this->idColegioActivo();
        $sesion    = $this->sesionActiva();

        $sesion['partidos'] = $this->aplicarEdiciones($sesion['partidos'], $request->input('filas', []));
        session([self::CLAVE_SESION => $sesion]);

        try {
            $resultado = $this->importador->importarLote(
                $idColegio,
                (int) $sesion['idTorneo'],
                $sesion['partidos'],
                Auth::id(),
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        session()->forget(self::CLAVE_SESION);

        return redirect()
            ->route('designaciones.index', ['torneo' => $sesion['idTorneo']])
            ->with('success', "Se importaron {$resultado['creados']} partido(s) correctamente.");
    }

    public function cancelar(): RedirectResponse
    {
        session()->forget(self::CLAVE_SESION);

        return redirect()->route('designaciones.importar.mostrar');
    }

    /** @return array<string, mixed> */
    private function sesionActiva(): array
    {
        $sesion = session(self::CLAVE_SESION);
        abort_unless(is_array($sesion) && (int) $sesion['idColegio'] === $this->idColegioActivo(), 404);

        return $sesion;
    }

    /**
     * @param  array<int, array<string, mixed>>  $partidos
     * @param  array<string, array<string, mixed>>  $filasEditadas  Indexadas por 'clave', tal como las envía el formulario.
     * @return array<int, array<string, mixed>>
     */
    private function aplicarEdiciones(array $partidos, array $filasEditadas): array
    {
        return array_map(function (array $filaActual) use ($filasEditadas) {
            $editado = $filasEditadas[$filaActual['clave']] ?? [];

            // Si el campo no vino en el submit (payload parcial), se conserva
            // el valor que ya estaba en sesión — nunca se asume vacío/null.
            $filaActual['grupoTexto']      = $editado['grupoTexto'] ?? $filaActual['grupoTexto'];
            $filaActual['equipoLocal']     = array_key_exists('equipoLocal', $editado)
                ? trim((string) $editado['equipoLocal']) : $filaActual['equipoLocal'];
            $filaActual['equipoVisitante'] = array_key_exists('equipoVisitante', $editado)
                ? trim((string) $editado['equipoVisitante']) : $filaActual['equipoVisitante'];
            $filaActual['fechaPartido'] = array_key_exists('fechaPartido', $editado)
                ? ($editado['fechaPartido'] ?: null) : $filaActual['fechaPartido'];
            $filaActual['horaPartido'] = array_key_exists('horaPartido', $editado)
                ? ($editado['horaPartido'] ?: null) : $filaActual['horaPartido'];
            $filaActual['idDivisionMatch'] = array_key_exists('idDivision', $editado)
                ? $this->aEnteroONulo($editado['idDivision']) : $filaActual['idDivisionMatch'];
            $filaActual['idSedeMatch'] = array_key_exists('idSede', $editado)
                ? $this->aEnteroONulo($editado['idSede']) : $filaActual['idSedeMatch'];
            $filaActual['idFormato'] = array_key_exists('idFormato', $editado)
                ? $this->aEnteroONulo($editado['idFormato']) : $filaActual['idFormato'];
            $filaActual['incluir'] = isset($editado['incluir']);

            [$errores, $advertencias] = $this->validar($filaActual);
            $filaActual['errores']      = $errores;
            $filaActual['advertencias'] = $advertencias;
            if ($errores !== []) {
                $filaActual['incluir'] = false;
            }

            return $filaActual;
        }, $partidos);
    }

    /**
     * El middleware ConvertEmptyStringsToNull convierte los <select> vacíos
     * a null antes de llegar aquí (no a '') — hay que tratar ambos igual,
     * o (int) null castea a 0 y viola la FK de idSede/idDivision/idFormato.
     */
    private function aEnteroONulo(mixed $valor): ?int
    {
        return ($valor === null || $valor === '') ? null : (int) $valor;
    }

    /**
     * @param  array<int, array{id: int, nombre: string}>  $divisiones
     * @param  array<int, array{id: int, nombre: string}>  $sedes
     */
    private function construirFila(array $crudo, array $divisiones, array $sedes, int $idFormatoDefault): array
    {
        $idDivisionMatch = $this->matcher->matchear($crudo['categoriaTexto'], $divisiones);
        $idSedeMatch     = $this->matcher->matchear($crudo['nombreSedeTexto'], $sedes);

        $fila = [
            'clave'           => $crudo['clave'] ?? (string) Str::uuid(),
            'grupoTexto'      => $crudo['grupoTexto'],
            'categoriaTexto'  => $crudo['categoriaTexto'],
            'fechaTexto'      => $crudo['fechaTexto'],
            'asociacionTexto' => $crudo['asociacionTexto'],
            'equipoLocal'     => $crudo['equipoLocal'],
            'equipoVisitante' => $crudo['equipoVisitante'],
            'nombreSedeTexto' => $crudo['nombreSedeTexto'],
            'diaTexto'        => $crudo['diaTexto'],
            'ciudadTexto'     => $crudo['ciudadTexto'],
            'fechaPartido'    => $crudo['fechaPartido'],
            'horaPartido'     => $crudo['horaPartido'],
            'idDivisionMatch' => $idDivisionMatch,
            'idSedeMatch'     => $idSedeMatch,
            'idFormato'       => $idFormatoDefault,
            'incluir'         => true,
        ];

        [$errores, $advertencias] = $this->validar($fila);
        $fila['errores']      = $errores;
        $fila['advertencias'] = $advertencias;
        $fila['incluir']      = $errores === [];

        return $fila;
    }

    /** @return array{0: string[], 1: string[]} [errores, advertencias] */
    private function validar(array $fila): array
    {
        $errores      = [];
        $advertencias = [];

        if ($fila['idDivisionMatch'] === null) {
            $errores[] = "División no encontrada para \"{$fila['categoriaTexto']}\" — selecciónala manualmente.";
        }

        if ($fila['equipoLocal'] === '' || $fila['equipoVisitante'] === '') {
            $errores[] = 'Equipo local o visitante vacío.';
        }

        if ($fila['fechaPartido'] === null || $fila['horaPartido'] === null) {
            $errores[] = 'No se pudo interpretar la fecha u hora — corrígela manualmente.';
        }

        if ($fila['idFormato'] === null) {
            $errores[] = 'Falta seleccionar el formato de designación.';
        }

        if ($fila['idSedeMatch'] === null) {
            $advertencias[] = "Sede \"{$fila['nombreSedeTexto']}\" no encontrada — se importará sin sede.";
        }

        return [$errores, $advertencias];
    }
}

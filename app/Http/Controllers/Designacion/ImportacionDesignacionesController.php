<?php

declare(strict_types=1);

namespace App\Http\Controllers\Designacion;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Designacion\ProcesarImportacionWordRequest;
use App\Models\DivisionTorneo;
use App\Models\FormatoDesignacion;
use App\Models\ImportacionPartidos;
use App\Models\SedeTorneo;
use App\Models\Torneo;
use App\Services\Importacion\ImportacionPartidosService;
use App\Services\Importacion\MatchingTextoService;
use App\Services\Importacion\PartidoWordParser;
use App\Services\Importacion\RevertirImportacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Importa partidos desde el .docx que las asociaciones envían a los
 * colegios (formato consistente: bloque GRUPO/CATEGORÍA/FECHA en rojo +
 * tabla de 4 filas por partido). La importación (cabecera + filas) se
 * persiste en BD desde el primer momento — no en sesión — para que quede
 * auditoría de quién importó qué y cuándo, se pueda retomar el preview tras
 * cerrar la sesión de trabajo, y se pueda revertir una importación ya
 * confirmada. Ver docs/plan-importador-word-designaciones.md.
 */
class ImportacionDesignacionesController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly PartidoWordParser $parser,
        private readonly MatchingTextoService $matcher,
        private readonly ImportacionPartidosService $importador,
        private readonly RevertirImportacionService $revertidor,
    ) {}

    public function mostrar(): View
    {
        $idColegio   = $this->idColegioActivo();
        $importacion = ImportacionPartidos::where('idColegio', $idColegio)
            ->where('estado', ImportacionPartidos::ESTADO_PROCESANDO)
            ->latest('idImportacion')
            ->first();

        if ($importacion !== null) {
            return view('designaciones.importar', [
                'importacion' => $importacion,
                'filas'       => $importacion->filas()->orderBy('idFila')->get(),
                'divisiones'  => DivisionTorneo::where('idTorneo', $importacion->idTorneo)->orderBy('nombreDivision')->get(),
                'sedes'       => SedeTorneo::where('idTorneo', $importacion->idTorneo)->orderBy('nombreSede')->get(),
                'formatos'    => FormatoDesignacion::activos()->get(),
                'arbitros'    => $this->matcher->arbitrosDelColegio($idColegio),
            ]);
        }

        $torneos = Torneo::where('idColegio', $idColegio)
            ->whereIn('estadoTorneo', ['activo', 'proximo'])
            ->orderByDesc('temporada')
            ->limit(100)
            ->get();

        $formatos = FormatoDesignacion::activos()->get();

        return view('designaciones.importar', [
            'importacion' => null, 'filas' => null, 'torneos' => $torneos, 'formatos' => $formatos,
        ]);
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

        $this->importador->crearDesdeParseo(
            $idColegio,
            $torneo,
            (int) Auth::id(),
            $request->file('archivoWord')->getClientOriginalName(),
            (int) $datos['idFormato'],
            $crudos,
        );

        return redirect()->route('designaciones.importar.mostrar');
    }

    public function revisar(Request $request): RedirectResponse
    {
        $importacion = $this->importacionEnRevision();

        $this->importador->aplicarEdiciones($importacion, $request->input('filas', []));

        return redirect()->route('designaciones.importar.mostrar')->with('success', 'Correcciones guardadas.');
    }

    /**
     * Aplica las mismas correcciones que "Guardar correcciones" antes de
     * importar — el botón "Confirmar" del preview envía las mismas filas,
     * así que un cambio hecho justo antes de confirmar (sin pasar primero
     * por "Guardar correcciones") no se pierde.
     */
    public function confirmar(Request $request): RedirectResponse
    {
        $importacion = $this->importacionEnRevision();

        $this->importador->aplicarEdiciones($importacion, $request->input('filas', []));

        try {
            $resultado = $this->importador->confirmar($importacion->fresh(), (int) Auth::id());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('designaciones.index', ['torneo' => $resultado->idTorneo])
            ->with('success', "Se importaron {$resultado->totalCreados} partido(s) correctamente.");
    }

    public function cancelar(): RedirectResponse
    {
        $importacion = $this->importacionEnRevision();
        $importacion->update(['estado' => ImportacionPartidos::ESTADO_CANCELADA]);

        return redirect()->route('designaciones.importar.mostrar');
    }

    /**
     * Historial de importaciones del colegio (cualquier estado) — auditoría
     * de quién importó qué archivo, cuándo, y cuántos partidos generó.
     */
    public function historial(): View
    {
        $idColegio = $this->idColegioActivo();

        $importaciones = ImportacionPartidos::where('idColegio', $idColegio)
            ->with(['torneo', 'usuario', 'usuarioReversion'])
            ->orderByDesc('idImportacion')
            ->paginate(20);

        return view('designaciones.importar-historial', compact('importaciones'));
    }

    /** Deshace una importación ya confirmada, si todos sus partidos siguen en borrador y sin pagos. */
    public function revertir(int $idImportacion): RedirectResponse
    {
        $idColegio   = $this->idColegioActivo();
        $importacion = ImportacionPartidos::where('idColegio', $idColegio)->findOrFail($idImportacion);

        try {
            $this->revertidor->revertir($importacion, $idColegio, (int) Auth::id());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Importación revertida — los partidos que creó fueron eliminados.');
    }

    private function importacionEnRevision(): ImportacionPartidos
    {
        $idColegio = $this->idColegioActivo();

        return ImportacionPartidos::where('idColegio', $idColegio)
            ->where('estado', ImportacionPartidos::ESTADO_PROCESANDO)
            ->latest('idImportacion')
            ->firstOrFail();
    }
}

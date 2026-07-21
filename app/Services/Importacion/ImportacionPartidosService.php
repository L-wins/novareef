<?php

declare(strict_types=1);

namespace App\Services\Importacion;

use App\Models\Arbitro;
use App\Models\ImportacionPartidoFila;
use App\Models\ImportacionPartidos;
use App\Models\Partido;
use App\Models\RolPartido;
use App\Models\Torneo;
use App\Services\DesignacionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Ciclo de vida completo de una importación de partidos desde Word: crea la
 * cabecera + filas en BD (reemplaza el array de sesión de la versión
 * anterior — da auditoría, permite retomar el preview tras cerrar sesión, y
 * habilita revertir una importación ya confirmada), aplica las ediciones del
 * usuario sobre las filas, y confirma creando los partidos + designaciones
 * reales cuando hubo match de árbitro.
 */
final class ImportacionPartidosService
{
    /** Nombre de rol (roles_partido) que corresponde a cada etiqueta del Word. */
    private const ROL_POR_ETIQUETA = [
        'ARBITRO'    => 'Central',
        'LINEA UNO'  => 'Asistente',
        'LINEA DOS'  => 'Asistente',
        'EMERGENTE'  => 'Cuarto',
    ];

    public function __construct(
        private readonly MatchingTextoService $matcher,
        private readonly DeteccionDuplicadosPartidoService $duplicados,
        private readonly DesignacionService $designaciones,
    ) {}

    /**
     * Crea la cabecera de importación y sus filas ya matcheadas/validadas a
     * partir del resultado crudo del parser.
     *
     * @param  array<int, array<string, mixed>>  $filasCrudas  Salida de PartidoWordParser::parsear().
     */
    public function crearDesdeParseo(
        int $idColegio,
        Torneo $torneo,
        int $idUsuario,
        string $nombreArchivoOriginal,
        int $idFormatoDefault,
        array $filasCrudas,
    ): ImportacionPartidos {
        return DB::transaction(function () use ($idColegio, $torneo, $idUsuario, $nombreArchivoOriginal, $idFormatoDefault, $filasCrudas) {
            $importacion = ImportacionPartidos::create([
                'idColegio'              => $idColegio,
                'idTorneo'               => $torneo->idTorneo,
                'idUsuario'              => $idUsuario,
                'nombreArchivoOriginal'  => $nombreArchivoOriginal,
                'idFormatoDefault'       => $idFormatoDefault,
                'estado'                 => ImportacionPartidos::ESTADO_PROCESANDO,
                'totalFilas'             => count($filasCrudas),
            ]);

            $divisiones = $this->matcher->divisionesDelTorneo($torneo->idTorneo);
            $sedes      = $this->matcher->sedesDelTorneo($torneo->idTorneo);
            $arbitros   = $this->matcher->arbitrosDelColegio($idColegio);
            $existentes = $this->duplicados->existentesDelTorneo($torneo->idTorneo);

            foreach ($filasCrudas as $crudo) {
                $this->crearFila($importacion, $crudo, $divisiones, $sedes, $arbitros, $existentes, $idFormatoDefault);
            }

            return $importacion;
        });
    }

    /**
     * Aplica las correcciones manuales del preview sobre las filas ya
     * persistidas — mismo criterio que antes (payload parcial nunca borra
     * un valor que no vino en el submit), pero ahora sobre registros de BD.
     *
     * @param  array<string, array<string, mixed>>  $filasEditadas  Indexadas por 'clave'.
     */
    public function aplicarEdiciones(ImportacionPartidos $importacion, array $filasEditadas): void
    {
        $existentes = $this->duplicados->existentesDelTorneo($importacion->idTorneo);

        DB::transaction(function () use ($importacion, $filasEditadas, $existentes) {
            foreach ($importacion->filas as $fila) {
                $editado = $filasEditadas[$fila->clave] ?? [];

                if ($editado === [] && ! array_key_exists($fila->clave, $filasEditadas)) {
                    // Fila que ni siquiera vino en el submit (ej. deshabilitada
                    // por JS) — se conserva tal cual, no se toca ningún campo.
                    continue;
                }

                $fila->grupoTexto      = $editado['grupoTexto'] ?? $fila->grupoTexto;
                $fila->equipoLocal     = array_key_exists('equipoLocal', $editado)
                    ? trim((string) $editado['equipoLocal']) : $fila->equipoLocal;
                $fila->equipoVisitante = array_key_exists('equipoVisitante', $editado)
                    ? trim((string) $editado['equipoVisitante']) : $fila->equipoVisitante;
                $fila->fechaPartido = array_key_exists('fechaPartido', $editado)
                    ? ($editado['fechaPartido'] ?: null) : $fila->fechaPartido;
                $fila->horaPartido = array_key_exists('horaPartido', $editado)
                    ? ($editado['horaPartido'] ?: null) : $fila->horaPartido;
                $fila->idDivisionMatch = array_key_exists('idDivision', $editado)
                    ? $this->aEnteroONulo($editado['idDivision']) : $fila->idDivisionMatch;
                $fila->idSedeMatch = array_key_exists('idSede', $editado)
                    ? $this->aEnteroONulo($editado['idSede']) : $fila->idSedeMatch;
                $fila->idFormato = array_key_exists('idFormato', $editado)
                    ? $this->aEnteroONulo($editado['idFormato']) : $fila->idFormato;
                $fila->incluir = isset($editado['incluir']);

                // Reasignación manual de árbitro por rol desde el preview:
                // designaciones[idRol] = idArbitro (0/"" = dejar el slot vacío).
                if (isset($editado['designaciones']) && is_array($editado['designaciones'])) {
                    $fila->designacionesMatch = $this->aplicarEdicionDesignaciones(
                        $fila->designacionesMatch ?? [],
                        $editado['designaciones'],
                    );
                }

                $fila->esPosibleDuplicado = $this->duplicados->esPosibleDuplicado(
                    $existentes,
                    $fila->idDivisionMatch,
                    $fila->equipoLocal,
                    $fila->equipoVisitante,
                    $fila->fechaPartido?->format('Y-m-d'),
                );

                [$errores, $advertencias] = $this->validar($fila);
                $fila->errores      = $errores;
                $fila->advertencias = $advertencias;
                if ($errores !== []) {
                    $fila->incluir = false;
                }

                $fila->save();
            }
        });
    }

    /**
     * Confirma la importación: crea un partido por cada fila incluida (todo
     * o nada), y para cada rol con árbitro matcheado crea también la
     * designación real — así el partido llega ya designado cuando el Word
     * traía nombres reconocibles, sin volver a asignar a mano en NovaReef.
     *
     * @throws \RuntimeException  Si alguna fila incluida sigue teniendo error bloqueante.
     */
    public function confirmar(ImportacionPartidos $importacion, int $idUsuarioAccion): ImportacionPartidos
    {
        $filasAImportar = $importacion->filas()->where('incluir', true)->get();

        foreach ($filasAImportar as $fila) {
            if ($fila->tieneErrores()) {
                throw new \RuntimeException(
                    "El partido {$fila->equipoLocal} vs {$fila->equipoVisitante} tiene errores sin resolver: "
                    . implode(' — ', $fila->errores),
                );
            }
        }

        return DB::transaction(function () use ($importacion, $filasAImportar, $idUsuarioAccion) {
            $creados = 0;

            foreach ($filasAImportar as $fila) {
                $partido = $this->designaciones->crearPartido($importacion->idColegio, [
                    'idTorneo'        => $importacion->idTorneo,
                    'idDivision'      => $fila->idDivisionMatch,
                    'idSede'          => $fila->idSedeMatch,
                    'idFormato'       => $fila->idFormato,
                    'idImportacion'   => $importacion->idImportacion,
                    'equipoLocal'     => $fila->equipoLocal,
                    'equipoVisitante' => $fila->equipoVisitante,
                    'fechaPartido'    => $fila->fechaPartido?->format('Y-m-d'),
                    'horaPartido'     => $fila->horaPartido,
                    'observaciones'   => trim((string) $fila->grupoTexto) ?: null,
                ], $idUsuarioAccion);

                $this->designarArbitrosMatcheados($partido, $fila, $importacion->idColegio, $idUsuarioAccion);

                $fila->update(['idPartidoCreado' => $partido->idPartido]);
                $creados++;
            }

            $importacion->update([
                'estado'       => ImportacionPartidos::ESTADO_CONFIRMADA,
                'totalCreados' => $creados,
                'confirmadaEn' => now(),
            ]);

            return $importacion->fresh();
        });
    }

    private function designarArbitrosMatcheados(Partido $partido, ImportacionPartidoFila $fila, int $idColegio, int $idUsuarioAccion): void
    {
        foreach ($fila->designacionesMatch ?? [] as $rol) {
            $idArbitro = $rol['idArbitroMatch'] ?? null;
            if ($idArbitro === null) {
                continue;
            }

            try {
                $this->designaciones->asignarArbitro($partido, (int) $idArbitro, (int) $rol['idRol'], $idColegio, $idUsuarioAccion);
            } catch (\RuntimeException) {
                // Slot ya ocupado (ej. dos roles del Word matchearon al mismo
                // árbitro) o árbitro ya designado en el partido — el partido
                // se crea igual, solo ese rol queda sin designar y se corrige
                // a mano desde la vista del partido, como cualquier otro slot vacío.
            }
        }
    }

    /**
     * @param  array<int, array{id: int, nombre: string}>  $divisiones
     * @param  array<int, array{id: int, nombre: string}>  $sedes
     * @param  array<int, array{id: int, nombre: string}>  $arbitros
     * @param  array<string, true>  $existentes
     */
    private function crearFila(
        ImportacionPartidos $importacion,
        array $crudo,
        array $divisiones,
        array $sedes,
        array $arbitros,
        array $existentes,
        int $idFormatoDefault,
    ): ImportacionPartidoFila {
        $idDivisionMatch = $this->matcher->matchear($crudo['categoriaTexto'], $divisiones);
        $idSedeMatch     = $this->matcher->matchear($crudo['nombreSedeTexto'], $sedes);

        $designacionesMatch = $this->matchearRoles($crudo['roles'] ?? [], $arbitros, $importacion->idColegio);

        $esPosibleDuplicado = $this->duplicados->esPosibleDuplicado(
            $existentes, $idDivisionMatch, $crudo['equipoLocal'], $crudo['equipoVisitante'], $crudo['fechaPartido'],
        );

        $fila = new ImportacionPartidoFila([
            'idImportacion'       => $importacion->idImportacion,
            'clave'               => $crudo['clave'] ?? (string) Str::uuid(),
            'grupoTexto'          => $crudo['grupoTexto'],
            'categoriaTexto'      => $crudo['categoriaTexto'],
            'fechaTexto'          => $crudo['fechaTexto'],
            'asociacionTexto'     => $crudo['asociacionTexto'],
            'nombreSedeTexto'     => $crudo['nombreSedeTexto'],
            'diaTexto'            => $crudo['diaTexto'],
            'ciudadTexto'         => $crudo['ciudadTexto'],
            'rolesTexto'          => $crudo['roles'] ?? [],
            'equipoLocal'         => $crudo['equipoLocal'],
            'equipoVisitante'     => $crudo['equipoVisitante'],
            'fechaPartido'        => $crudo['fechaPartido'],
            'horaPartido'         => $crudo['horaPartido'],
            'idDivisionMatch'     => $idDivisionMatch,
            'idSedeMatch'         => $idSedeMatch,
            'idFormato'           => $idFormatoDefault,
            'designacionesMatch'  => $designacionesMatch,
            'esPosibleDuplicado'  => $esPosibleDuplicado,
            'incluir'             => true,
        ]);

        [$errores, $advertencias] = $this->validar($fila, $crudo['errorParseo'] ?? null);
        $fila->errores      = $errores;
        $fila->advertencias = $advertencias;
        $fila->incluir      = $errores === [];
        $fila->save();

        return $fila;
    }

    /**
     * Matchea cada rol (ARBITRO/LINEA UNO/LINEA DOS/EMERGENTE) por nombre
     * dentro del colegio activo. Cualquiera de los 4 puede pertenecer a otra
     * asociación (colegio) — si no matchea dentro de este colegio, el slot
     * queda sin árbitro con una advertencia; nunca se busca en otro tenant.
     *
     * @param  array<int, array{rolTexto: string, nombreTexto: string, asociacionTexto: ?string}>  $roles
     * @param  array<int, array{id: int, nombre: string}>  $arbitros
     * @return array<int, array{idRol: int, rolTexto: string, nombreTexto: string,
     *                            asociacionTexto: ?string, idArbitroMatch: ?int,
     *                            sugerenciaNombre: ?string, sugerenciaId: ?int}>
     */
    private function matchearRoles(array $roles, array $arbitros, int $idColegio): array
    {
        $rolesDb = RolPartido::where('esActivo', true)
            ->whereIn('nombre', array_unique(self::ROL_POR_ETIQUETA))
            ->pluck('idRol', 'nombre');

        $resultado = [];

        foreach ($roles as $rol) {
            $nombreRolDestino = self::ROL_POR_ETIQUETA[$rol['rolTexto']] ?? null;
            $idRol            = $nombreRolDestino ? $rolesDb->get($nombreRolDestino) : null;

            if ($idRol === null || trim($rol['nombreTexto']) === '') {
                continue;
            }

            $match = $this->matcher->matchearConSugerencia($rol['nombreTexto'], $arbitros);

            $resultado[] = [
                'idRol'            => (int) $idRol,
                'rolTexto'         => $rol['rolTexto'],
                'nombreTexto'      => $rol['nombreTexto'],
                'asociacionTexto'  => $rol['asociacionTexto'],
                'idArbitroMatch'   => $match['idMatch'],
                'sugerenciaId'     => $match['sugerencia']['id'] ?? null,
                'sugerenciaNombre' => $match['sugerencia']['nombre'] ?? null,
            ];
        }

        return $resultado;
    }

    /**
     * @param  array<int, array<string, mixed>>  $designacionesActuales
     * @param  array<string, mixed>  $edicion  [idRol => idArbitro|'', ...]
     * @return array<int, array<string, mixed>>
     */
    private function aplicarEdicionDesignaciones(array $designacionesActuales, array $edicion): array
    {
        return array_map(function (array $rol) use ($edicion) {
            if (array_key_exists((string) $rol['idRol'], $edicion)) {
                $rol['idArbitroMatch'] = $this->aEnteroONulo($edicion[(string) $rol['idRol']]);
            }

            return $rol;
        }, $designacionesActuales);
    }

    private function aEnteroONulo(mixed $valor): ?int
    {
        return ($valor === null || $valor === '') ? null : (int) $valor;
    }

    /** @return array{0: string[], 1: string[]} [errores, advertencias] */
    private function validar(ImportacionPartidoFila $fila, ?string $errorParseo = null): array
    {
        $errores      = [];
        $advertencias = [];

        if ($errorParseo !== null) {
            $errores[] = $errorParseo;
        }

        if ($fila->idDivisionMatch === null) {
            $errores[] = "División no encontrada para \"{$fila->categoriaTexto}\" — selecciónala manualmente.";
        }

        if ($fila->equipoLocal === '' || $fila->equipoVisitante === '') {
            $errores[] = 'Equipo local o visitante vacío.';
        }

        if ($fila->fechaPartido === null || $fila->horaPartido === null) {
            $errores[] = 'No se pudo interpretar la fecha u hora — corrígela manualmente.';
        }

        if ($fila->idFormato === null) {
            $errores[] = 'Falta seleccionar el formato de designación.';
        }

        if ($fila->idSedeMatch === null) {
            $advertencias[] = "Sede \"{$fila->nombreSedeTexto}\" no encontrada — se importará sin sede.";
        }

        if ($fila->esPosibleDuplicado) {
            $advertencias[] = 'Ya existe un partido con estos mismos equipos, división y fecha en este torneo — revisa que no sea un duplicado.';
        }

        foreach ($fila->designacionesMatch ?? [] as $rol) {
            if ($rol['idArbitroMatch'] === null && $rol['sugerenciaNombre'] === null) {
                $advertencias[] = "{$rol['rolTexto']} \"{$rol['nombreTexto']}\" no encontrado en este colegio"
                    . ($rol['asociacionTexto'] ? " (asociación: {$rol['asociacionTexto']})" : '')
                    . ' — se dejará el rol sin asignar.';
            } elseif ($rol['idArbitroMatch'] === null && $rol['sugerenciaNombre'] !== null) {
                $advertencias[] = "{$rol['rolTexto']} \"{$rol['nombreTexto']}\": ¿quisiste decir \"{$rol['sugerenciaNombre']}\"? Confírmalo manualmente.";
            }
        }

        return [$errores, $advertencias];
    }
}

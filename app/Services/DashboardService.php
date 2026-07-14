<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Arbitro;
use Illuminate\Support\Collection;

/**
 * Arma el payload del dashboard de cada rol componiendo servicios de cada
 * dominio que ya existen — nunca queries propias. Un método público por rol,
 * cada uno devuelve solo lo que su vista necesita.
 */
final class DashboardService
{
    /** Accesos rápidos por módulo — solo los consume el dashboard de ejecutivo. */
    private const MODULOS = [
        ['key' => 'arbitros',      'permiso' => 'ver-arbitros',      'ruta' => 'arbitros.index',           'icono' => 'fa-users',           'color' => 'ic-teal',    'nombre' => 'Árbitros',      'desc' => 'Expedientes, categorías y estadísticas'],
        ['key' => 'torneos',       'permiso' => 'ver-torneos',       'ruta' => 'torneos.index',            'icono' => 'fa-trophy',          'color' => 'ic-amber',   'nombre' => 'Torneos',       'desc' => 'Competencias, equipos y partidos'],
        ['key' => 'designaciones', 'permiso' => 'ver-designaciones', 'ruta' => 'designaciones.index',      'icono' => 'fa-clipboard-list',  'color' => 'ic-emerald', 'nombre' => 'Designaciones', 'desc' => 'Asignación de árbitros a partidos'],
        ['key' => 'finanzas',      'permiso' => 'ver-finanzas',      'ruta' => 'finanzas.index',           'icono' => 'fa-money-bill-wave', 'color' => 'ic-blue',    'nombre' => 'Finanzas',      'desc' => 'Pagos, cuotas e ingresos del colegio'],
        ['key' => 'academico',     'permiso' => 'ver-academico',     'ruta' => 'academico.sesiones.index', 'icono' => 'fa-graduation-cap',  'color' => 'ic-purple',  'nombre' => 'Académico',     'desc' => 'Cursos, evaluaciones y formación'],
        ['key' => 'sanciones',     'permiso' => 'ver-sanciones',     'ruta' => 'sanciones.index',          'icono' => 'fa-gavel',           'color' => 'ic-red',     'nombre' => 'Sanciones',     'desc' => 'Gestión disciplinaria de árbitros'],
    ];

    public function __construct(
        private readonly LimiteService $limites,
        private readonly FinanzasService $finanzas,
        private readonly ReporteFinanzasService $reporteFinanzas,
        private readonly ReporteDesignacionesService $designaciones,
        private readonly SancionService $sanciones,
        private readonly JustificacionAcademicaService $justificaciones,
        private readonly SesionAcademicaService $sesiones,
        private readonly DisponibilidadService $disponibilidad,
    ) {}

    /**
     * @return array{
     *     arbitrosRegistrados: int, arbitrosActivos: int, arbitrosProceso: int, totalUsuarios: int,
     *     limiteArbitrosUsados: int, limiteArbitros: ?int, limiteArbitrosPorcentaje: float,
     *     bolsillos: array, designaciones: array, sanciones: array,
     *     sesionesProximas: Collection, modulos: array,
     * }
     */
    public function paraEjecutivo(int $idColegio): array
    {
        $arbitrosRegistrados = Arbitro::where('idColegio', $idColegio)->count();

        return [
            'arbitrosActivos'          => Arbitro::where('idColegio', $idColegio)->where('estadoArbitro', 'activo')->count(),
            'arbitrosProceso'          => Arbitro::where('idColegio', $idColegio)->where('estadoArbitro', 'proceso_ingreso')->count(),
            'limiteArbitrosUsados'     => $arbitrosRegistrados,
            'limiteArbitros'           => $this->limites->limiteArbitros($idColegio),
            'limiteArbitrosPorcentaje' => $this->limites->porcentajeUsoArbitros($idColegio),
            'bolsillos'                => $this->reporteFinanzas->bolsillos($idColegio),
            'designaciones'            => $this->designaciones->resumenParaDashboard($idColegio),
            'sanciones'                => $this->sanciones->resumenParaDashboard($idColegio),
            'sesionesProximas'         => $this->sesiones->proximasDelColegio($idColegio),
            'modulos'                  => self::MODULOS,
        ];
    }

    /**
     * @return array{bolsillos: array, topDeudas: Collection}
     */
    public function paraTesorero(int $idColegio): array
    {
        $balance = $this->reporteFinanzas->balanceGeneral($idColegio);

        return [
            'bolsillos' => $this->reporteFinanzas->bolsillos($idColegio),
            'topDeudas' => $balance['porArbitro']->take(5),
        ];
    }

    /**
     * @return array{criticosCount: int, hoyCount: int, pendientesConfirmacion: Collection}
     */
    public function paraDesignador(int $idColegio): array
    {
        return $this->designaciones->resumenParaDashboard($idColegio);
    }

    /**
     * @return array{
     *     activasCount: int, apelacionesPendientes: int, recientes: Collection,
     *     justificacionesPendientesCount: int, justificacionesPendientes: Collection,
     * }
     */
    public function paraSanciones(int $idColegio): array
    {
        return [
            ...$this->sanciones->resumenParaDashboard($idColegio),
            'justificacionesPendientesCount' => $this->justificaciones->pendientesCount($idColegio),
            'justificacionesPendientes'      => $this->justificaciones->pendientes($idColegio),
        ];
    }

    /**
     * @return array{sesionesProximas: Collection, sesionesAbiertasAhoraCount: int, justificacionesPendientesCount: int}
     */
    public function paraTecnico(int $idColegio): array
    {
        return [
            'sesionesProximas'              => $this->sesiones->proximasDelColegio($idColegio),
            'sesionesAbiertasAhoraCount'     => $this->sesiones->sesionesAbiertasAhoraCount($idColegio),
            'justificacionesPendientesCount' => $this->justificaciones->pendientesCount($idColegio),
        ];
    }

    /**
     * @return array{
     *     saldoPendienteCobrar: float, proximosPartidos: Collection, proximasClases: Collection,
     *     yaReportoDisponibilidad: bool, porcentajeAsistencia: ?float,
     * }
     */
    public function paraArbitro(Arbitro $arbitro): array
    {
        return [
            'saldoPendienteCobrar'    => $this->finanzas->saldoPendienteArbitro($arbitro),
            'proximosPartidos'        => $this->designaciones->proximasDesignacionesArbitro($arbitro),
            'proximasClases'          => $this->sesiones->proximasDelArbitro($arbitro),
            'yaReportoDisponibilidad' => $this->disponibilidad->yaReportoEstaSemana($arbitro),
            'porcentajeAsistencia'    => $arbitro->porcentajeAsistencia,
        ];
    }

    /**
     * @return array{partidosPendientesDeCalificar: Collection}
     */
    public function paraVeedor(int $idUsuario): array
    {
        return [
            'partidosPendientesDeCalificar' => $this->designaciones->partidosPendientesDeCalificar($idUsuario),
        ];
    }
}

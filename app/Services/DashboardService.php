<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Arbitro;
use App\Models\Partido;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Arma el payload del dashboard de cada rol componiendo servicios de cada
 * dominio que ya existen — nunca queries propias. Un método público por rol,
 * cada uno devuelve solo lo que su vista necesita.
 */
final class DashboardService
{
    /**
     * TTL corto (no invalidación manual dispersa por cada punto de escritura
     * de árbitros/finanzas/designaciones/sanciones/académico que alimenta
     * este payload) — medido con datos de carga reales (idColegio con 200
     * árbitros y 200 partidos): paraEjecutivo() tardaba ~122ms y ejecutaba
     * 24 queries por carga. El dashboard de ejecutivo es la pantalla de
     * aterrizaje más visitada del panel — 60s evita repetir ese costo en
     * refrescos/navegación de ida y vuelta sin arriesgar datos obsoletos por
     * más de un minuto.
     */
    private const TTL_DASHBOARD_EJECUTIVO = 60;

    /** Accesos rápidos por módulo — solo los consume el dashboard de ejecutivo. */
    private const MODULOS = [
        ['key' => 'arbitros',      'permiso' => 'ver-arbitros',      'ruta' => 'arbitros.index',           'icono' => 'fa-users',           'color' => 'ic-teal',    'nombre' => 'Árbitros',      'desc' => 'Expedientes, categorías y estadísticas'],
        ['key' => 'torneos',       'permiso' => 'ver-torneos',       'ruta' => 'torneos.index',            'icono' => 'fa-trophy',          'color' => 'ic-amber',   'nombre' => 'Torneos',       'desc' => 'Competencias, equipos y partidos'],
        ['key' => 'designaciones', 'permiso' => 'ver-designaciones', 'ruta' => 'designaciones.index',      'icono' => 'fa-clipboard-list',  'color' => 'ic-emerald', 'nombre' => 'Designaciones', 'desc' => 'Asignación de árbitros a partidos'],
        ['key' => 'finanzas',      'permiso' => 'ver-finanzas',      'ruta' => 'finanzas.balance.index',   'icono' => 'fa-money-bill-wave', 'color' => 'ic-blue',    'nombre' => 'Finanzas',      'desc' => 'Pagos, cuotas e ingresos del colegio'],
        ['key' => 'academico',     'permiso' => 'ver-academico',     'ruta' => 'academico.sesiones.index', 'icono' => 'fa-graduation-cap',  'color' => 'ic-purple',  'nombre' => 'Académico',     'desc' => 'Cursos, evaluaciones y formación'],
        ['key' => 'sanciones',     'permiso' => 'ver-sanciones',     'ruta' => 'sanciones.index',          'icono' => 'fa-gavel',           'color' => 'ic-red',     'nombre' => 'Sanciones',     'desc' => 'Gestión disciplinaria de árbitros'],
    ];

    public function __construct(
        private readonly LimiteService $limites,
        private readonly ColegioService $colegios,
        private readonly FinanzasService $finanzas,
        private readonly BalanceFinanzasService $balanceFinanzas,
        private readonly EstadoCuentaArbitroService $estadoCuenta,
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
     *     sesionesProximas: Collection, modulos: array, partidosNominaSinGenerar: int,
     * }
     */
    public function paraEjecutivo(int $idColegio): array
    {
        $datos = Cache::remember(
            "dashboard.ejecutivo.{$idColegio}",
            self::TTL_DASHBOARD_EJECUTIVO,
            function () use ($idColegio): array {
                // Una sola query SUM condicional (ColegioService::estadisticasArbitros)
                // en vez de 3 queries COUNT sueltas — mismo dato que ya
                // calculaba AdminColegioController::show(), reusado en vez de
                // reimplementado (auditoría de duplicación, julio 2026).
                $stats = $this->colegios->estadisticasArbitros($idColegio);

                return [
                    'arbitrosActivos'          => $stats['activos'],
                    'arbitrosProceso'          => $stats['enProceso'],
                    'limiteArbitrosUsados'     => $stats['total'],
                    'limiteArbitros'           => $this->limites->limiteArbitros($idColegio),
                    'limiteArbitrosPorcentaje' => $this->limites->porcentajeUsoArbitros($idColegio),
                    'bolsillos'                => $this->balanceFinanzas->bolsillos($idColegio),
                    'designaciones'            => $this->designaciones->resumenParaDashboard($idColegio),
                    'sanciones'                => $this->sanciones->resumenParaDashboard($idColegio),
                    'sesionesProximas'         => $this->sesiones->proximasDelColegio($idColegio),
                    'modulos'                  => self::MODULOS,
                ];
            },
        );

        // criticosCount queda fuera del caché a propósito: un partido crítico
        // necesita reasignación antes de la hora del partido — es información
        // urgente, no un contador de reporte. Verificado en la auditoría de
        // carga: con el resto del payload cacheado a 60s, un rechazo de
        // último momento podía dejar el dashboard mostrando "0 críticos"
        // hasta un minuto después de que el partido ya necesitara atención.
        // Es una sola query barata (idx_partidos_estado), recalcularla en
        // cada carga no repite el costo que sí justificaba cachear el resto.
        $datos['designaciones']['criticosCount'] = Partido::where('idColegio', $idColegio)
            ->where('estadoPartido', Partido::ESTADO_CRITICO)
            ->count();

        // Mismo criterio que criticosCount: nunca cacheado, y visible tanto
        // para ejecutivo como tesorero (ver paraTesorero) — es la alerta que
        // faltaba para el hallazgo de "nómina silenciosa" de la auditoría de
        // carga (docs/auditoria-carga-2026-07.md): antes de esto, un partido
        // finalizado sin tarifa configurada o con designaciones sin confirmar
        // no generaba ningún aviso visible, solo un Log::warning() en servidor.
        $datos['partidosNominaSinGenerar'] = $this->balanceFinanzas->partidosNominaSinGenerar($idColegio);

        return $datos;
    }

    /**
     * @return array{bolsillos: array, topDeudas: Collection, partidosNominaSinGenerar: int}
     */
    public function paraTesorero(int $idColegio): array
    {
        $balance = $this->balanceFinanzas->balanceGeneral($idColegio);

        return [
            'bolsillos'                => $this->balanceFinanzas->bolsillosDesdeBalance($balance),
            'topDeudas'                => $balance['porArbitro']->take(5),
            'partidosNominaSinGenerar' => $this->balanceFinanzas->partidosNominaSinGenerar($idColegio),
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
            'saldoPendienteCobrar'    => $this->estadoCuenta->saldoPendienteArbitro($arbitro),
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

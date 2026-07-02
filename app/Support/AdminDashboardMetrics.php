<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Arbitro;
use App\Models\Colegio;
use App\Models\Suscripcion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class AdminDashboardMetrics
{
    public readonly int $totalColegios;
    public readonly int $colegiosActivos;
    public readonly int $colegiosTrial;
    public readonly int $totalArbitros;
    public readonly Collection $ultimosColegios;

    private function __construct() {}

    /**
     * Carga todas las métricas del dashboard en el mínimo de queries posible:
     *   - 1 query para los contadores de colegios (selectRaw condicional)
     *   - 1 query para colegios en trial
     *   - 1 query para árbitros
     *   - 1 query para los últimos 5 colegios
     */
    public static function cargar(): self
    {
        $instance = new self();

        // Contadores de colegios en una sola query con SUM condicional.
        $contadores = Colegio::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(estadoColegio = 'activo') as activos")
            ->first();

        $instance->totalColegios   = (int) $contadores->total;
        $instance->colegiosActivos = (int) $contadores->activos;

        $instance->colegiosTrial = Suscripcion::where('estado', 'trial')
            ->distinct('idColegio')
            ->count('idColegio');

        $instance->totalArbitros = Arbitro::count();

        // Solo los campos que renderiza la vista — sin traer columnas innecesarias.
        $instance->ultimosColegios = Colegio::select([
                'idColegio', 'nombreColegio', 'emailColegio',
                'codigoColegio', 'paisColegio', 'estadoColegio', 'created_at',
            ])
            ->with(['suscripcionActiva.plan:idPlan,nombre'])
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        return $instance;
    }
}

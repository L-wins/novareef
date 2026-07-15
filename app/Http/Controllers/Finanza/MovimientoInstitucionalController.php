<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finanza;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finanza\StoreMovimientoInstitucionalRequest;
use App\Models\Torneo;
use App\Services\FinanzasService;
use App\Services\ReporteFinanzasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Gastos e ingresos institucionales: los 5 tipos de movimiento que no
 * cuelgan de un árbitro (ingreso de torneo, otro ingreso, gasto fijo,
 * gasto institucional, gasto vario) — la ficha del árbitro cubre nómina,
 * mensualidad y multa; esto cubre lo demás.
 *
 * Nacen ya pagados (FinanzasService::registrarMovimientoPagado()): si se
 * está registrando un ingreso o gasto institucional es porque el dinero ya
 * se movió (el torneo ya pagó, ya se pagó el arriendo), no hay un estado
 * "pendiente" real que valga la pena modelar aquí — por eso no hay
 * abonar()/anular(): cada movimiento queda con exactamente un abono desde
 * el momento en que se crea, y una corrección se hace registrando un nuevo
 * movimiento, no anulando el original (mismo criterio que saldo_inicial).
 */
class MovimientoInstitucionalController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly FinanzasService $finanzas,
        private readonly ReporteFinanzasService $reportes,
    ) {}

    public function index(Request $request): View
    {
        $idColegio = $this->idColegioActivo();
        $filtros   = $request->only(['tipoMovimiento', 'categoria', 'estado', 'q', 'desde', 'hasta']);

        $movimientos = $this->reportes->queryInstitucional($idColegio, $filtros)
            ->with(['abonos', 'historial.usuarioAccion'])
            ->orderByDesc('fechaMovimiento')
            ->orderByDesc('idMovimiento')
            ->paginate(20)
            ->withQueryString();

        $resumen = $this->reportes->resumenInstitucional($idColegio, $filtros);

        // Acotado a los últimos 100 no cancelados — sin esto, el desplegable
        // crece sin límite con cada temporada nueva. "finalizado" sí se
        // incluye a propósito: el ingreso de un torneo suele llegar después
        // de que termina.
        $torneos = Torneo::where('idColegio', $idColegio)
            ->where('estadoTorneo', '!=', 'cancelado')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get(['idTorneo', 'nombreTorneo']);

        return view('finanzas.institucional.index', compact('movimientos', 'resumen', 'filtros', 'torneos'));
    }

    public function store(StoreMovimientoInstitucionalRequest $request): RedirectResponse
    {
        try {
            $this->finanzas->registrarMovimientoPagado($this->idColegioActivo(), $request->validated(), Auth::user());
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('finanzas.institucional.index')
            ->with('success', 'Movimiento registrado correctamente.');
    }
}

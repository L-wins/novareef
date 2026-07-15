<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finanza;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finanza\PagarNominaArbitroRequest;
use App\Http\Requests\Finanza\StoreAbonoArbitroRequest;
use App\Http\Requests\Finanza\StoreCargoArbitroRequest;
use App\Models\Arbitro;
use App\Models\MovimientoFinanciero;
use App\Services\FinanzasService;
use App\Services\ReporteFinanzasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Ficha financiera de un árbitro específico: historial completo en las dos
 * direcciones (lo que se le debe / lo que nos debe) y todas las acciones de
 * escritura individuales — abonar/anular un cargo, pagar nómina (uno a la
 * vez o en lote) y compensar una deuda contra la nómina disponible —,
 * siempre scopeadas a este árbitro, nunca globales. Reemplaza la vieja
 * vista de "pago acumulado".
 */
class FichaFinancieraArbitroController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly FinanzasService $finanzas,
        private readonly ReporteFinanzasService $reportes,
    ) {}

    public function show(int $idArbitro): View
    {
        $arbitro        = $this->arbitroDelColegio($idArbitro, ['usuario', 'categoria']);
        $estadoCuenta   = $this->reportes->estadoCuentaArbitro($arbitro);
        $lotesRecientes = $this->reportes->lotesRecientes($this->idColegioActivo(), $arbitro->idArbitro);

        return view('finanzas.arbitro.show', compact('arbitro', 'estadoCuenta', 'lotesRecientes'));
    }

    public function store(StoreCargoArbitroRequest $request, int $idArbitro): RedirectResponse
    {
        $arbitro = $this->arbitroDelColegio($idArbitro);
        $datos   = $request->validated();

        // Multa manual — sin origen formal (Sanciones se engancha directo al
        // Service, no pasa por este formulario).
        $tipoOrigenMulta = $datos['categoria'] === MovimientoFinanciero::CATEGORIA_MULTA
            ? MovimientoFinanciero::ORIGEN_MULTA_MANUAL
            : null;

        try {
            $this->finanzas->registrarMovimiento($this->idColegioActivo(), [
                'tipoMovimiento'  => MovimientoFinanciero::TIPO_INGRESO,
                'categoria'       => $datos['categoria'],
                'concepto'        => $datos['concepto'],
                'montoTotal'      => $datos['montoTotal'],
                'fechaMovimiento' => $datos['fechaMovimiento'],
                'idArbitro'       => $arbitro->idArbitro,
                'observaciones'   => $datos['observaciones'] ?? null,
                'tipoOrigenMulta' => $tipoOrigenMulta,
            ], Auth::user());
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('finanzas.arbitro.show', $arbitro->idArbitro)
            ->with('success', 'Cargo registrado correctamente.');
    }

    public function abonar(StoreAbonoArbitroRequest $request, int $idArbitro, int $idMovimiento): RedirectResponse
    {
        $arbitro    = $this->arbitroDelColegio($idArbitro);
        $movimiento = $this->movimientoDelArbitro($arbitro, $idMovimiento);

        try {
            $this->finanzas->registrarAbono($movimiento, $request->validated(), Auth::user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('finanzas.arbitro.show', $arbitro->idArbitro)
            ->with('success', 'Abono registrado correctamente.');
    }

    public function anular(int $idArbitro, int $idMovimiento): RedirectResponse
    {
        $arbitro    = $this->arbitroDelColegio($idArbitro);
        $movimiento = $this->movimientoDelArbitro($arbitro, $idMovimiento);

        try {
            $this->finanzas->anularMovimiento($movimiento, Auth::user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('finanzas.arbitro.show', $arbitro->idArbitro)
            ->with('success', 'Movimiento anulado correctamente.');
    }

    /** Pago en efectivo/transferencia de uno o varios partidos de nómina, en un solo lote. */
    public function pagarNomina(PagarNominaArbitroRequest $request, int $idArbitro): RedirectResponse
    {
        $arbitro = $this->arbitroDelColegio($idArbitro);
        $datos   = $request->validated();

        try {
            $resultado = $this->finanzas->pagarNominaArbitro($arbitro, $datos['idsMovimientos'], [
                'fecha'      => $datos['fecha'],
                'metodoPago' => $datos['metodoPago'],
                'referencia' => $datos['referencia'] ?? null,
            ], Auth::user());
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('finanzas.arbitro.show', $arbitro->idArbitro)
            ->with('success', sprintf('Pago registrado: $%s.', number_format($resultado['total'], 0, ',', '.')))
            ->with('lotePago', $resultado['idLotePago']);
    }

    /** Compensa una deuda (mensualidad/multa) contra la nómina pendiente del árbitro, hasta donde alcance. */
    public function compensar(int $idArbitro, int $idMovimiento): RedirectResponse
    {
        $arbitro = $this->arbitroDelColegio($idArbitro);
        $deuda   = $this->movimientoDelArbitro($arbitro, $idMovimiento);

        try {
            $resultado = $this->finanzas->compensarDeudaConNomina($arbitro, $deuda, Auth::user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('finanzas.arbitro.show', $arbitro->idArbitro)
            ->with('success', sprintf('Se compensaron $%s contra la nómina pendiente.', number_format($resultado['montoCompensado'], 0, ',', '.')));
    }

    /** Comprobante PDF de un lote de pago/compensación de este árbitro. */
    public function comprobante(int $idArbitro, string $lote): mixed
    {
        $this->arbitroDelColegio($idArbitro);

        $datos = $this->reportes->datosComprobante($lote, $this->idColegioActivo());

        abort_if($datos === null, 404);

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('pdf.comprobante-pago', [
            'datos'       => $datos,
            'idLotePago'  => $lote,
            'colegio'     => $datos['arbitro']->colegio ?? null,
            'generadoPor' => Auth::user(),
        ]);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("comprobante-pago-{$datos['fecha']->format('Y-m-d')}-" . substr($lote, 0, 8) . '.pdf');
    }

    // ── Helpers privados ──────────────────

    /** @param  string[]  $with */
    private function arbitroDelColegio(int $idArbitro, array $with = []): Arbitro
    {
        return Arbitro::with($with)
            ->where('idColegio', $this->idColegioActivo())
            ->findOrFail($idArbitro);
    }

    /** Un movimiento solo se puede abonar/anular/compensar desde acá si es de este mismo árbitro. */
    private function movimientoDelArbitro(Arbitro $arbitro, int $idMovimiento): MovimientoFinanciero
    {
        return MovimientoFinanciero::where('idArbitro', $arbitro->idArbitro)
            ->where('idColegio', $arbitro->idColegio)
            ->findOrFail($idMovimiento);
    }
}

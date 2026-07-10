<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finanza;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finanza\PagarAcumuladoRequest;
use App\Models\Arbitro;
use App\Models\MovimientoFinanciero;
use App\Services\FinanzasService;
use App\Services\ReporteFinanzasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PagoArbitroController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly FinanzasService $finanzas,
        private readonly ReporteFinanzasService $reportes,
    ) {}

    public function index(Request $request): View
    {
        $idColegio = $this->idColegioActivo();

        $arbitros = Arbitro::where('idColegio', $idColegio)->with('usuario')->get();

        $arbitroSeleccionado = null;
        $movimientosNomina   = collect();
        $deudas              = collect();
        $lotesRecientes      = collect();

        if ($request->filled('idArbitro')) {
            $arbitroSeleccionado = Arbitro::where('idColegio', $idColegio)
                ->findOrFail($request->integer('idArbitro'));

            $movimientosNomina = MovimientoFinanciero::where('idColegio', $idColegio)
                ->where('idArbitro', $arbitroSeleccionado->idArbitro)
                ->whereIn('categoria', [MovimientoFinanciero::CATEGORIA_NOMINA_ARBITRO, MovimientoFinanciero::CATEGORIA_ARBITRO_EXTERNO])
                ->whereIn('estadoMovimiento', [MovimientoFinanciero::ESTADO_PENDIENTE, MovimientoFinanciero::ESTADO_PARCIAL])
                ->with('partido.torneo')
                ->conTotalAbonado()
                ->orderBy('fechaMovimiento')
                ->get();

            $deudas = MovimientoFinanciero::where('idColegio', $idColegio)
                ->where('idArbitro', $arbitroSeleccionado->idArbitro)
                ->whereIn('categoria', [MovimientoFinanciero::CATEGORIA_MENSUALIDAD, MovimientoFinanciero::CATEGORIA_MULTA])
                ->whereIn('estadoMovimiento', [MovimientoFinanciero::ESTADO_PENDIENTE, MovimientoFinanciero::ESTADO_PARCIAL])
                ->conTotalAbonado()
                ->orderBy('fechaMovimiento')
                ->get();

            $lotesRecientes = $this->reportes->lotesRecientes($idColegio, $arbitroSeleccionado->idArbitro);
        }

        return view('finanzas.pagos-arbitro.index', compact('arbitros', 'arbitroSeleccionado', 'movimientosNomina', 'deudas', 'lotesRecientes'));
    }

    public function store(PagarAcumuladoRequest $request): RedirectResponse
    {
        $idColegio = $this->idColegioActivo();
        $datos     = $request->validated();

        $arbitro = Arbitro::where('idColegio', $idColegio)->findOrFail($datos['idArbitro']);

        try {
            $resultado = $this->finanzas->pagarAcumuladoArbitro(
                $arbitro,
                $datos['idsMovimientosNomina'],
                $datos['idsDeudasNetear'] ?? [],
                [
                    'fecha'      => $datos['fecha'],
                    'metodoPago' => $datos['metodoPago'],
                    'referencia' => $datos['referencia'] ?? null,
                ],
                Auth::user(),
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('finanzas.pagos-arbitro.index', ['idArbitro' => $arbitro->idArbitro])
            ->with('success', sprintf(
                'Pago registrado: $%s neto desembolsado.',
                number_format($resultado['netoDesembolsado'], 0, ',', '.'),
            ))
            ->with('lotePago', $resultado['idLotePago']);
    }

    /**
     * Comprobante PDF de un pago acumulado (lote) — para el tesorero/ejecutivo.
     */
    public function comprobante(string $lote): mixed
    {
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
}

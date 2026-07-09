<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finanza;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finanza\PagarAcumuladoRequest;
use App\Models\Arbitro;
use App\Models\MovimientoFinanciero;
use App\Services\FinanzasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PagoArbitroController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly FinanzasService $finanzas,
    ) {}

    public function index(Request $request): View
    {
        $idColegio = $this->idColegioActivo();

        $arbitros = Arbitro::where('idColegio', $idColegio)->with('usuario')->get();

        $arbitroSeleccionado = null;
        $movimientosNomina   = collect();
        $deudas              = collect();

        if ($request->filled('idArbitro')) {
            $arbitroSeleccionado = Arbitro::where('idColegio', $idColegio)
                ->findOrFail($request->integer('idArbitro'));

            $movimientosNomina = MovimientoFinanciero::where('idColegio', $idColegio)
                ->where('idArbitro', $arbitroSeleccionado->idArbitro)
                ->whereIn('categoria', [MovimientoFinanciero::CATEGORIA_NOMINA_ARBITRO, MovimientoFinanciero::CATEGORIA_ARBITRO_EXTERNO])
                ->whereIn('estadoMovimiento', [MovimientoFinanciero::ESTADO_PENDIENTE, MovimientoFinanciero::ESTADO_PARCIAL])
                ->with('partido.torneo')
                ->orderBy('fechaMovimiento')
                ->get();

            $deudas = MovimientoFinanciero::where('idColegio', $idColegio)
                ->where('idArbitro', $arbitroSeleccionado->idArbitro)
                ->whereIn('categoria', [MovimientoFinanciero::CATEGORIA_MENSUALIDAD, MovimientoFinanciero::CATEGORIA_MULTA])
                ->whereIn('estadoMovimiento', [MovimientoFinanciero::ESTADO_PENDIENTE, MovimientoFinanciero::ESTADO_PARCIAL])
                ->orderBy('fechaMovimiento')
                ->get();
        }

        return view('finanzas.pagos-arbitro.index', compact('arbitros', 'arbitroSeleccionado', 'movimientosNomina', 'deudas'));
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
            ->route('finanzas.pagos-arbitro.index')
            ->with('success', sprintf(
                'Pago registrado: $%s neto desembolsado (lote %s).',
                number_format($resultado['netoDesembolsado'], 2),
                $resultado['idLotePago'],
            ));
    }
}

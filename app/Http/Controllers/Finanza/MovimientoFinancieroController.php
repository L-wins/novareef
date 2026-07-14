<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finanza;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finanza\StoreAbonoRequest;
use App\Http\Requests\Finanza\StoreMovimientoRequest;
use App\Models\Arbitro;
use App\Models\MovimientoFinanciero;
use App\Models\Torneo;
use App\Services\FinanzasService;
use App\Services\ReporteFinanzasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MovimientoFinancieroController extends Controller
{
    use ResuelveColegio;

    /** Filtros del listado que comparten índice, resumen y export CSV. */
    private const FILTROS = ['tipoMovimiento', 'categoria', 'estado', 'idArbitro', 'idTorneo', 'q', 'desde', 'hasta'];

    public function __construct(
        private readonly FinanzasService $finanzas,
        private readonly ReporteFinanzasService $reportes,
    ) {}

    public function index(Request $request): View
    {
        $idColegio = $this->idColegioActivo();
        $filtros   = $request->only(self::FILTROS);

        $movimientos = $this->reportes->queryFiltrada($idColegio, $filtros)
            ->with(['arbitro.usuario', 'torneo'])
            ->conTotalAbonado()
            ->orderByDesc('fechaMovimiento')
            ->orderByDesc('idMovimiento')
            ->paginate(20)
            ->withQueryString();

        $resumen = $this->reportes->resumenListado($idColegio, $filtros);

        $arbitrosFiltro = Arbitro::where('idColegio', $idColegio)
            ->whereHas('movimientosFinancieros')
            ->with('usuario')
            ->get();

        $torneosFiltro = Torneo::where('idColegio', $idColegio)
            ->whereHas('movimientosFinancieros')
            ->orderByDesc('temporada')
            ->get();

        return view('finanzas.index', compact('movimientos', 'resumen', 'arbitrosFiltro', 'torneosFiltro'));
    }

    /**
     * Exporta el listado (con los filtros activos) a CSV — separador ';' y
     * BOM UTF-8 para que Excel en español lo abra bien sin importar nada.
     */
    public function exportarCsv(Request $request): StreamedResponse
    {
        $idColegio = $this->idColegioActivo();
        $filtros   = $request->only(self::FILTROS);

        $movimientos = $this->reportes->queryFiltrada($idColegio, $filtros)
            ->with(['arbitro.usuario', 'torneo'])
            ->conTotalAbonado()
            ->orderByDesc('fechaMovimiento')
            ->orderByDesc('idMovimiento');

        $nombre = 'movimientos-financieros-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($movimientos): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM — Excel detecta UTF-8

            fputcsv($out, ['Fecha', 'Tipo', 'Categoría', 'Concepto', 'Árbitro', 'Torneo', 'Monto', 'Abonado', 'Saldo', 'Estado'], ';');

            foreach ($movimientos->lazy() as $mov) {
                $abonado = (float) $mov->montoTotal - $mov->saldoPendiente();

                fputcsv($out, [
                    $mov->fechaMovimiento->format('Y-m-d'),
                    $mov->esIngreso() ? 'Ingreso' : 'Egreso',
                    $mov->etiquetaCategoria(),
                    $mov->concepto,
                    $mov->arbitro?->usuario?->nombreUsuario ?? $mov->nombreArbitroExterno ?? '',
                    $mov->torneo?->nombreTorneo ?? '',
                    number_format((float) $mov->montoTotal, 0, ',', ''),
                    number_format($abonado, 0, ',', ''),
                    number_format($mov->saldoPendiente(), 0, ',', ''),
                    MovimientoFinanciero::ETIQUETAS_ESTADO[$mov->estadoMovimiento][0] ?? $mov->estadoMovimiento,
                ], ';');
            }

            fclose($out);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function create(): View
    {
        $idColegio = $this->idColegioActivo();

        $arbitros = Arbitro::where('idColegio', $idColegio)
            ->with('usuario')
            ->whereNotIn('estadoArbitro', ['retirado'])
            ->get();

        $torneos = Torneo::where('idColegio', $idColegio)->orderByDesc('temporada')->get();

        return view('finanzas.create', compact('arbitros', 'torneos'));
    }

    public function store(StoreMovimientoRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        // Multa manual — sin origen formal (Sanciones y Académico son otros
        // puntos de entrada que se enganchan directamente al Service, no a este formulario).
        if ($datos['categoria'] === MovimientoFinanciero::CATEGORIA_MULTA) {
            $datos['tipoOrigenMulta'] = MovimientoFinanciero::ORIGEN_MULTA_MANUAL;
        }

        try {
            $movimiento = $this->finanzas->registrarMovimiento($this->idColegioActivo(), $datos, Auth::user());
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('finanzas.show', $movimiento->idMovimiento)
            ->with('success', 'Movimiento registrado correctamente.');
    }

    public function show(int $id): View
    {
        $movimiento = $this->movimientoDelColegio($id, ['arbitro.usuario', 'torneo', 'partido', 'designacion.rol', 'usuarioRegistro', 'abonos.usuarioRegistro', 'historial.usuarioAccion']);

        return view('finanzas.show', compact('movimiento'));
    }

    public function abonar(StoreAbonoRequest $request, int $id): RedirectResponse
    {
        $movimiento = $this->movimientoDelColegio($id);

        try {
            $this->finanzas->registrarAbono($movimiento, $request->validated(), Auth::user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('finanzas.show', $movimiento->idMovimiento)
            ->with('success', 'Abono registrado correctamente.');
    }

    public function anular(int $id): RedirectResponse
    {
        $movimiento = $this->movimientoDelColegio($id);

        try {
            $this->finanzas->anularMovimiento($movimiento, Auth::user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('finanzas.show', $movimiento->idMovimiento)
            ->with('success', 'Movimiento anulado correctamente.');
    }

    // ── Helpers privados ──────────────────

    private function movimientoDelColegio(int $id, array $relaciones = []): MovimientoFinanciero
    {
        $movimiento = MovimientoFinanciero::with($relaciones)->findOrFail($id);

        abort_unless((int) $movimiento->idColegio === $this->idColegioActivo(), 403);

        return $movimiento;
    }
}

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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MovimientoFinancieroController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly FinanzasService $finanzas,
    ) {}

    public function index(Request $request): View
    {
        $idColegio = $this->idColegioActivo();

        $movimientos = MovimientoFinanciero::where('idColegio', $idColegio)
            ->with(['arbitro.usuario', 'torneo'])
            ->when($request->filled('tipoMovimiento'), fn ($q) => $q->where('tipoMovimiento', $request->string('tipoMovimiento')))
            ->when($request->filled('categoria'), fn ($q) => $q->where('categoria', $request->string('categoria')))
            ->when($request->filled('estado'), fn ($q) => $q->where('estadoMovimiento', $request->string('estado')))
            ->when($request->filled('desde'), fn ($q) => $q->where('fechaMovimiento', '>=', $request->string('desde')))
            ->when($request->filled('hasta'), fn ($q) => $q->where('fechaMovimiento', '<=', $request->string('hasta')))
            ->orderByDesc('fechaMovimiento')
            ->orderByDesc('idMovimiento')
            ->paginate(20)
            ->withQueryString();

        return view('finanzas.index', compact('movimientos'));
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

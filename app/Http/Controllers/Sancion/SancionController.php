<?php

declare(strict_types=1);

namespace App\Http\Controllers\Sancion;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sancion\CambiarEstadoSancionRequest;
use App\Http\Requests\Sancion\StoreSancionRequest;
use App\Models\Arbitro;
use App\Models\Sancion;
use App\Models\TipoSancion;
use App\Services\SancionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SancionController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly SancionService $sanciones,
    ) {}

    public function index(Request $request): View
    {
        $idColegio = $this->idColegioActivo();
        $esArbitro = Auth::user()->rolUsuario === 'arbitro';

        $query = Sancion::where('idColegio', $idColegio)
            ->with(['arbitro.usuario', 'tipo']);

        if ($esArbitro) {
            $arbitro = $this->arbitroAutenticado();
            $query->where('idArbitro', $arbitro->idArbitro);
        } else {
            $query->when($request->filled('idArbitro'), fn ($q) => $q->where('idArbitro', $request->integer('idArbitro')))
                  ->when($request->filled('estado'), fn ($q) => $q->where('estadoSancion', $request->string('estado')));
        }

        $sanciones = $query->orderByDesc('fechaHecho')->paginate(20)->withQueryString();

        $arbitros = $esArbitro ? collect() : Arbitro::where('idColegio', $idColegio)->with('usuario')->get();

        return view('sanciones.index', compact('sanciones', 'arbitros', 'esArbitro'));
    }

    public function create(): View
    {
        $idColegio = $this->idColegioActivo();

        $arbitros = Arbitro::where('idColegio', $idColegio)->with('usuario')->get();
        $tipos    = TipoSancion::where('idColegio', $idColegio)->where('esActivo', true)->orderBy('nombre')->get();

        return view('sanciones.create', compact('arbitros', 'tipos'));
    }

    public function store(StoreSancionRequest $request): RedirectResponse
    {
        try {
            $sancion = $this->sanciones->crearSancion($this->idColegioActivo(), $request->validated(), Auth::user());
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('sanciones.show', $sancion->idSancion)
            ->with('success', 'Sanción registrada correctamente.');
    }

    public function show(int $id): View
    {
        $sancion = $this->sancionDelColegio($id, ['arbitro.usuario', 'tipo', 'partido', 'usuarioImpuso', 'movimientoFinanciero', 'historial.usuarioAccion']);

        if (Auth::user()->rolUsuario === 'arbitro') {
            $arbitro = $this->arbitroAutenticado();
            abort_unless((int) $sancion->idArbitro === $arbitro->idArbitro, 403);
        }

        return view('sanciones.show', compact('sancion'));
    }

    public function cambiarEstado(CambiarEstadoSancionRequest $request, int $id): RedirectResponse
    {
        $sancion = $this->sancionDelColegio($id);
        $datos   = $request->validated();

        if (in_array($datos['accion'], ['anular', 'resolver'], true)) {
            abort_unless(Auth::user()->can('editar-sanciones'), 403, 'No tienes permiso para anular o resolver apelaciones.');
        }

        try {
            match ($datos['accion']) {
                'cumplir'  => $this->sanciones->cumplir($sancion, Auth::user(), $datos['motivo'] ?? null),
                'anular'   => $this->sanciones->anular($sancion, Auth::user(), $datos['motivo']),
                'apelar'   => $this->sanciones->apelar($sancion, Auth::user(), $datos['motivo'] ?? null),
                'resolver' => $this->sanciones->resolverApelacion($sancion, $datos['resultado'], Auth::user(), $datos['motivo'] ?? null),
            };
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('sanciones.show', $sancion->idSancion)
            ->with('success', 'Estado de la sanción actualizado.');
    }

    private function sancionDelColegio(int $id, array $relaciones = []): Sancion
    {
        $sancion = Sancion::with($relaciones)->findOrFail($id);

        abort_unless((int) $sancion->idColegio === $this->idColegioActivo(), 403);

        return $sancion;
    }
}

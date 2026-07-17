<?php

declare(strict_types=1);

namespace App\Http\Controllers\Torneo;

use App\Http\Controllers\Concerns\AutorizaTorneo;
use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Torneo\CambiarEstadoTorneoRequest;
use App\Http\Requests\Torneo\GuardarPerfilTorneoRequest;
use App\Http\Requests\Torneo\StoreTorneoRequest;
use App\Http\Requests\Torneo\UpdateTorneoRequest;
use App\Models\FormatoDesignacion;
use App\Models\ReglamentoTorneo;
use App\Models\RolPartido;
use App\Models\MovimientoFinanciero;
use App\Models\Torneo;
use App\Services\ReporteFinanzasService;
use App\Services\TorneoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TorneoController extends Controller
{
    use ResuelveColegio, AutorizaTorneo;

    public function __construct(
        private readonly TorneoService $torneos,
        private readonly ReporteFinanzasService $reportes,
    ) {}

    public function index(Request $request): View
    {
        $idColegio = $this->idColegioActivo();

        $torneos = Torneo::with(['divisiones'])
            ->withCount(['partidos', 'divisiones'])
            ->where('idColegio', $idColegio)
            ->when($request->filled('estado'), fn ($q) => $q->where('estadoTorneo', $request->query('estado')))
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipoTorneo', $request->query('tipo')))
            ->when($request->filled('temporada'), fn ($q) => $q->where('temporada', $request->integer('temporada')))
            ->orderByDesc('fechaInicio')
            ->paginate(15)
            ->withQueryString();

        $temporadasDisponibles = Torneo::where('idColegio', $idColegio)
            ->distinct()
            ->orderByDesc('temporada')
            ->pluck('temporada');

        return view('torneos.index', compact('torneos', 'temporadasDisponibles'));
    }

    public function show(int $id): View
    {
        $torneo = Torneo::with([
                'divisiones.tarifas.rol',
                'divisiones.tarifas.formato',
                'sedes',
                'partidos.division',
                'partidos.sede',
                'partidos.formato',
                'creador',
                'reglamentoActual.subidoPor',
                'emergentes.arbitro.usuario',
                'emergentes.sede',
            ])
            ->findOrFail($id);

        $this->autorizarTorneo($torneo);

        $resumenCobro = null;
        if ($torneo->modalidadPago === 'nomina' && Auth::user()->can('ver-finanzas')) {
            $filtrosBase = ['idTorneo' => $torneo->idTorneo];

            $resumenCobro = [
                // totalEgresos aquí = nómina generada = lo que le corresponde cobrar al organizador (passthrough, sin margen)
                'nomina'   => $this->reportes->resumenListado($this->idColegioActivo(), [...$filtrosBase, 'categoria' => MovimientoFinanciero::CATEGORIA_NOMINA_ARBITRO]),
                // totalIngresos/pendientePorCobrar aquí = lo que el colegio ya registró manualmente como cobro recibido
                'ingresos' => $this->reportes->resumenListado($this->idColegioActivo(), [...$filtrosBase, 'categoria' => MovimientoFinanciero::CATEGORIA_INGRESO_TORNEO]),
            ];
        }

        return view('torneos.show', compact('torneo', 'resumenCobro'));
    }

    public function create(): View
    {
        return view('torneos.create');
    }

    public function store(StoreTorneoRequest $request): RedirectResponse
    {
        $torneo = Torneo::create([
            ...$request->validated(),
            'idColegio'        => $this->idColegioActivo(),
            'idUsuarioCreador' => Auth::id(),
            'estadoTorneo'     => 'proximo',
        ]);

        return redirect()
            ->route('torneos.perfil', $torneo->idTorneo)
            ->with('success', 'Torneo creado. Completa el perfil para agregar divisiones, sedes y tarifas.');
    }

    public function perfil(int $id): View
    {
        $torneo = Torneo::with([
                'divisiones.tarifas.rol',
                'divisiones.tarifas.formato',
                'sedes',
                'reglamentoActual.subidoPor',
                'reglamentos' => fn ($q) => $q->where('esActual', false)->latest('created_at'),
                'reglamentos.subidoPor',
            ])
            ->findOrFail($id);

        $this->autorizarTorneo($torneo);

        $roles    = RolPartido::activos()->get();
        $formatos = FormatoDesignacion::activos()->get();

        return view('torneos.perfil', compact('torneo', 'roles', 'formatos'));
    }

    public function guardarPerfil(GuardarPerfilTorneoRequest $request, int $id): RedirectResponse
    {
        $torneo = Torneo::findOrFail($id);

        $this->autorizarTorneo($torneo);

        if ($request->hasFile('reglamentoPDF')) {
            $this->torneos->subirReglamento($torneo, $request->file('reglamentoPDF'), Auth::id());

            return back()->with('success', 'Reglamento subido correctamente. La versión anterior queda en el historial.');
        }

        return back()->with('success', 'Cambios guardados correctamente.');
    }

    public function edit(int $id): View
    {
        $torneo = Torneo::withCount('partidos')->findOrFail($id);

        $this->autorizarTorneo($torneo);

        return view('torneos.edit', compact('torneo'));
    }

    public function update(UpdateTorneoRequest $request, int $id): RedirectResponse
    {
        $torneo = Torneo::findOrFail($id);

        $this->autorizarTorneo($torneo);

        $torneo->update($request->validated());

        return redirect()
            ->route('torneos.show', $torneo->idTorneo)
            ->with('success', 'Torneo actualizado correctamente.');
    }

    public function cambiarEstado(CambiarEstadoTorneoRequest $request, int $id): RedirectResponse
    {
        $torneo = Torneo::findOrFail($id);

        $this->autorizarTorneo($torneo);

        try {
            $this->torneos->cambiarEstado($torneo, $request->validated('estadoNuevo'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Estado del torneo actualizado correctamente.');
    }

    public function archivar(int $id): RedirectResponse
    {
        $torneo = Torneo::findOrFail($id);

        $this->autorizarTorneo($torneo);

        $torneo->delete();

        return redirect()
            ->route('torneos.index')
            ->with('success', 'Torneo archivado correctamente.');
    }

    public function eliminarReglamento(int $id): RedirectResponse
    {
        $reglamento = ReglamentoTorneo::with('torneo')->findOrFail($id);

        $this->autorizarTorneo($reglamento->torneo);

        $this->torneos->eliminarReglamento($reglamento);

        return back()->with('success', 'Reglamento eliminado correctamente.');
    }
}

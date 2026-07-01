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
use App\Models\Torneo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TorneoController extends Controller
{
    use ResuelveColegio, AutorizaTorneo;

    public function index(Request $request): View
    {
        $idColegio = $this->idColegioActivo();

        $query = Torneo::with(['divisiones'])
            ->withCount(['partidos', 'divisiones'])
            ->where('idColegio', $idColegio);

        if ($estado = $request->query('estado')) {
            $query->where('estadoTorneo', $estado);
        }

        if ($tipo = $request->query('tipo')) {
            $query->where('tipoTorneo', $tipo);
        }

        if ($temporada = $request->query('temporada')) {
            $query->where('temporada', (int) $temporada);
        }

        $torneos = $query->orderByDesc('fechaInicio')->paginate(15)->withQueryString();

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

        return view('torneos.show', compact('torneo'));
    }

    public function create(): View
    {
        return view('torneos.create');
    }

    public function store(StoreTorneoRequest $request): RedirectResponse
    {
        $torneo = DB::transaction(fn (): Torneo => Torneo::create([
            ...$request->validated(),
            'idColegio'        => $this->idColegioActivo(),
            'idUsuarioCreador' => Auth::id(),
            'estadoTorneo'     => 'proximo',
        ]));

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

        $roles    = RolPartido::where('esActivo', true)->orderBy('orden')->get();
        $formatos = FormatoDesignacion::where('esActivo', true)->orderBy('orden')->get();

        return view('torneos.perfil', compact('torneo', 'roles', 'formatos'));
    }

    public function guardarPerfil(GuardarPerfilTorneoRequest $request, int $id): RedirectResponse
    {
        $torneo = Torneo::findOrFail($id);

        $this->autorizarTorneo($torneo);

        if ($request->hasFile('reglamentoPDF')) {
            DB::transaction(function () use ($torneo, $request): void {
                ReglamentoTorneo::where('idTorneo', $torneo->idTorneo)
                    ->where('esActual', true)
                    ->update(['esActual' => false]);

                $archivo = $request->file('reglamentoPDF');
                $ruta    = $archivo->store('reglamentos', 'public');

                ReglamentoTorneo::create([
                    'idTorneo'        => $torneo->idTorneo,
                    'nombreArchivo'   => $archivo->getClientOriginalName(),
                    'rutaArchivo'     => $ruta,
                    'tamanoBytes'     => (int) $archivo->getSize(),
                    'esActual'        => true,
                    'idUsuarioSubida' => Auth::id(),
                ]);
            });

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

        $estadoNuevo = $request->validated('estadoNuevo');

        if ($torneo->estadoTorneo === $estadoNuevo) {
            return back()->with('error', 'El torneo ya está en ese estado.');
        }

        $torneo->update(['estadoTorneo' => $estadoNuevo]);

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

        $eraActual = $reglamento->esActual;
        $torneoId  = $reglamento->idTorneo;

        Storage::disk('public')->delete($reglamento->rutaArchivo);
        $reglamento->delete();

        if ($eraActual) {
            ReglamentoTorneo::where('idTorneo', $torneoId)
                ->latest('created_at')
                ->first()
                ?->update(['esActual' => true]);
        }

        return back()->with('success', 'Reglamento eliminado correctamente.');
    }
}

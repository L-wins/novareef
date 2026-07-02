<?php

declare(strict_types=1);

namespace App\Http\Controllers\Torneo;

use App\Http\Controllers\Concerns\AutorizaTorneo;
use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Torneo\StoreEmergenteRequest;
use App\Models\Arbitro;
use App\Models\EmergenteTorneo;
use App\Models\Torneo;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmergenteTorneoController extends Controller
{
    use ResuelveColegio, AutorizaTorneo;

    public function index(int $torneoId): View
    {
        $torneo = Torneo::with(['sedes', 'emergentes.arbitro.usuario', 'emergentes.sede'])
            ->findOrFail($torneoId);

        $this->autorizarTorneo($torneo);

        $arbitros = Arbitro::where('idColegio', $torneo->idColegio)
            ->where('estadoArbitro', 'activo')
            ->with('usuario')
            ->orderBy('idArbitro')
            ->get();

        // Una sola query — se particiona en PHP por fecha relativa a hoy.
        [$proximos, $historial] = EmergenteTorneo::where('idTorneo', $torneoId)
            ->with(['arbitro.usuario', 'sede'])
            ->orderBy('fechaEmergente', 'asc')
            ->get()
            ->partition(fn ($e) => $e->fechaEmergente->greaterThanOrEqualTo(Carbon::today()));

        $proximos  = $proximos->groupBy(fn ($e) => $e->fechaEmergente->format('Y-m-d'));
        $historial = $historial->sortByDesc('fechaEmergente')->groupBy(fn ($e) => $e->fechaEmergente->format('Y-m-d'));

        return view('torneos.emergentes.index', compact('torneo', 'arbitros', 'proximos', 'historial'));
    }

    public function store(StoreEmergenteRequest $request, int $torneoId): RedirectResponse
    {
        $torneo = Torneo::findOrFail($torneoId);

        $this->autorizarTorneo($torneo);

        $datos   = $request->validated();
        $arbitro = Arbitro::findOrFail($datos['idArbitro']);

        // Reglas de negocio: el árbitro debe estar activo y pertenecer al mismo colegio.
        abort_unless($arbitro->estadoArbitro === 'activo', 422, 'El árbitro seleccionado no está activo.');
        abort_unless((int) $arbitro->idColegio === $this->idColegioActivo(), 403, 'El árbitro pertenece a otro colegio.');

        abort_if(
            EmergenteTorneo::where('idTorneo', $torneo->idTorneo)
                ->where('idArbitro', $datos['idArbitro'])
                ->where('fechaEmergente', $datos['fechaEmergente'])
                ->exists(),
            422,
            'Este árbitro ya está asignado como emergente en esa fecha para este torneo.',
        );

        EmergenteTorneo::create([
            'idTorneo'           => $torneo->idTorneo,
            'idArbitro'          => $datos['idArbitro'],
            'idSede'             => $datos['idSede'],
            'fechaEmergente'     => $datos['fechaEmergente'],
            'notas'              => $datos['notas'] ?? null,
            'idUsuarioAsignador' => Auth::id(),
        ]);

        return back()->with('success', 'Emergente asignado correctamente.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $emergente = EmergenteTorneo::with('torneo')->findOrFail($id);

        $this->autorizarTorneo($emergente->torneo);

        $emergente->delete();

        return back()->with('success', 'Emergente eliminado correctamente.');
    }
}

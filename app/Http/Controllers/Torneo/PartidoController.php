<?php

declare(strict_types=1);

namespace App\Http\Controllers\Torneo;

use App\Http\Controllers\Concerns\AutorizaTorneo;
use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Torneo\CambiarEstadoPartidoRequest;
use App\Http\Requests\Torneo\StorePartidoRequest;
use App\Http\Requests\Torneo\UpdatePartidoRequest;
use App\Models\FormatoDesignacion;
use App\Models\Partido;
use App\Models\Torneo;
use App\Services\DesignacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PartidoController extends Controller
{
    use ResuelveColegio, AutorizaTorneo;

    public function __construct(
        private readonly DesignacionService $designaciones,
    ) {}

    public function index(Request $request, int $torneoId): View
    {
        $torneo = Torneo::with(['divisiones', 'sedes'])->findOrFail($torneoId);

        $this->autorizarTorneo($torneo);

        $query = Partido::with(['division', 'sede', 'formato'])
            ->where('idTorneo', $torneo->idTorneo);

        if ($estado = $request->query('estado')) {
            $query->where('estadoPartido', $estado);
        }

        if ($divisionId = $request->query('division')) {
            $query->where('idDivision', (int) $divisionId);
        }

        if ($fecha = $request->query('fecha')) {
            $query->whereDate('fechaPartido', $fecha);
        }

        $partidos = $query->orderBy('fechaPartido')->orderBy('horaPartido')
            ->paginate(20)->withQueryString();

        $formatos = FormatoDesignacion::activos()->get();

        return view('partidos.index', compact('torneo', 'partidos', 'formatos'));
    }

    public function store(StorePartidoRequest $request, int $torneoId): RedirectResponse
    {
        $torneo = Torneo::findOrFail($torneoId);

        $this->autorizarTorneo($torneo);

        // Nace en borrador: un partido nunca se publica sin árbitros — se
        // asignan y se publica desde el módulo de Designaciones (o desde el
        // modal de estado, que valida el Central antes de publicar).
        Partido::create([
            ...$request->validated(),
            'idTorneo'      => $torneo->idTorneo,
            'idColegio'     => $torneo->idColegio,
            'modalidadPago' => $torneo->modalidadPago,
            'estadoPartido' => Partido::ESTADO_BORRADOR,
        ]);

        return back()->with('success', 'Partido registrado en borrador. Asigna los árbitros desde Designaciones para publicarlo.');
    }

    /**
     * La ruta es torneos/{torneoId}/partidos/{id}/... — los parámetros llegan
     * POR POSICIÓN, así que la firma debe declarar ambos: con solo `int $id`
     * Laravel entregaba el torneoId en $id y se consultaba el partido equivocado.
     */
    public function update(UpdatePartidoRequest $request, int $torneoId, int $id): RedirectResponse
    {
        $partido = Partido::with('torneo')
            ->where('idTorneo', $torneoId)
            ->findOrFail($id);

        $this->autorizarTorneo($partido->torneo);

        $partido->update($request->validated());

        return back()->with('success', 'Partido actualizado correctamente.');
    }

    public function cambiarEstado(CambiarEstadoPartidoRequest $request, int $torneoId, int $id): RedirectResponse
    {
        $partido = Partido::with('torneo')
            ->where('idTorneo', $torneoId)
            ->findOrFail($id);

        $this->autorizarTorneo($partido->torneo);

        $estadoNuevo = $request->validated('estadoNuevo');

        // Publicar un borrador pasa por el servicio: exige al menos el árbitro
        // Central asignado y notifica a los árbitros (misma regla que el módulo
        // de Designaciones). Desde borrador no hay otro destino manual válido
        // aparte de cancelarlo.
        if ($partido->estadoPartido === Partido::ESTADO_BORRADOR) {
            if ($estadoNuevo === Partido::ESTADO_PROGRAMADO) {
                try {
                    $this->designaciones->publicarPartido($partido, Auth::user());
                } catch (\RuntimeException|\InvalidArgumentException $e) {
                    return back()->with('error', $e->getMessage());
                }

                return back()->with('success', 'Partido publicado. Los árbitros han sido notificados.');
            }

            if ($estadoNuevo !== Partido::ESTADO_CANCELADO) {
                return back()->with('error', 'Un partido en borrador solo puede publicarse o cancelarse.');
            }
        }

        $partido->update(['estadoPartido' => $estadoNuevo]);

        return back()->with('success', 'Estado del partido actualizado correctamente.');
    }
}

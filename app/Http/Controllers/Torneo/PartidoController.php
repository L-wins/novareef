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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PartidoController extends Controller
{
    use ResuelveColegio, AutorizaTorneo;

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

        $formatos = FormatoDesignacion::where('esActivo', true)->orderBy('orden')->get();

        return view('partidos.index', compact('torneo', 'partidos', 'formatos'));
    }

    public function store(StorePartidoRequest $request, int $torneoId): RedirectResponse
    {
        $torneo = Torneo::findOrFail($torneoId);

        $this->autorizarTorneo($torneo);

        Partido::create([
            ...$request->validated(),
            'idTorneo'      => $torneo->idTorneo,
            'idColegio'     => $torneo->idColegio,
            'modalidadPago' => $torneo->modalidadPago,
            'estadoPartido' => Partido::ESTADO_PROGRAMADO,
        ]);

        return back()->with('success', 'Partido registrado correctamente.');
    }

    public function update(UpdatePartidoRequest $request, int $id): RedirectResponse
    {
        $partido = Partido::with('torneo')->findOrFail($id);

        $this->autorizarTorneo($partido->torneo);

        $datos = collect($request->validated());

        // Resultados solo se persisten cuando el partido está finalizado o se está finalizando.
        if ($partido->estadoPartido !== Partido::ESTADO_FINALIZADO) {
            $datos = $datos->except(['resultadoLocal', 'resultadoVisitante']);
        }

        $partido->update($datos->toArray());

        return back()->with('success', 'Partido actualizado correctamente.');
    }

    public function cambiarEstado(CambiarEstadoPartidoRequest $request, int $id): RedirectResponse
    {
        $partido = Partido::with('torneo')->findOrFail($id);

        $this->autorizarTorneo($partido->torneo);

        $datos  = $request->validated();
        $update = ['estadoPartido' => $datos['estadoNuevo']];

        if ($datos['estadoNuevo'] === Partido::ESTADO_FINALIZADO) {
            $update['resultadoLocal']     = $datos['resultadoLocal'];
            $update['resultadoVisitante'] = $datos['resultadoVisitante'];
        }

        $partido->update($update);

        return back()->with('success', 'Estado del partido actualizado correctamente.');
    }
}

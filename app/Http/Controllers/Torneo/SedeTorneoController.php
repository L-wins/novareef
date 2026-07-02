<?php

declare(strict_types=1);

namespace App\Http\Controllers\Torneo;

use App\Http\Controllers\Concerns\AutorizaTorneo;
use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Torneo\SedeRequest;
use App\Models\Partido;
use App\Models\SedeTorneo;
use App\Models\Torneo;
use Illuminate\Http\RedirectResponse;

class SedeTorneoController extends Controller
{
    use ResuelveColegio, AutorizaTorneo;

    public function store(SedeRequest $request, int $torneoId): RedirectResponse
    {
        $torneo = Torneo::findOrFail($torneoId);

        $this->autorizarTorneo($torneo);

        SedeTorneo::create([
            'idTorneo' => $torneo->idTorneo,
            ...$request->validated(),
        ]);

        return back()->with('success', 'Sede agregada correctamente.');
    }

    public function update(SedeRequest $request, int $id): RedirectResponse
    {
        $sede = SedeTorneo::with('torneo')->findOrFail($id);

        $this->autorizarTorneo($sede->torneo);

        $sede->update($request->validated());

        return back()->with('success', 'Sede actualizada correctamente.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $sede = SedeTorneo::with('torneo')->findOrFail($id);

        $this->autorizarTorneo($sede->torneo);

        abort_if(
            Partido::where('idSede', $sede->idSede)->exists(),
            422,
            'No se puede eliminar una sede con partidos registrados.',
        );

        $sede->delete();

        return back()->with('success', 'Sede eliminada correctamente.');
    }
}

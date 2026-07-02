<?php

declare(strict_types=1);

namespace App\Http\Controllers\Torneo;

use App\Http\Controllers\Concerns\AutorizaTorneo;
use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Torneo\StoreDivisionRequest;
use App\Http\Requests\Torneo\UpdateDivisionRequest;
use App\Models\DivisionTorneo;
use App\Models\Partido;
use App\Models\Torneo;
use Illuminate\Http\RedirectResponse;

class DivisionTorneoController extends Controller
{
    use ResuelveColegio, AutorizaTorneo;

    public function store(StoreDivisionRequest $request, int $torneoId): RedirectResponse
    {
        $torneo = Torneo::findOrFail($torneoId);

        $this->autorizarTorneo($torneo);

        DivisionTorneo::create([
            'idTorneo' => $torneo->idTorneo,
            ...$request->validated(),
        ]);

        return back()->with('success', 'División agregada correctamente.');
    }

    public function update(UpdateDivisionRequest $request, int $id): RedirectResponse
    {
        $division = DivisionTorneo::with('torneo')->findOrFail($id);

        $this->autorizarTorneo($division->torneo);

        $division->update($request->validated());

        return back()->with('success', 'División actualizada correctamente.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $division = DivisionTorneo::with('torneo')->findOrFail($id);

        $this->autorizarTorneo($division->torneo);

        abort_if(
            Partido::where('idDivision', $division->idDivision)->exists(),
            422,
            'No se puede eliminar una división con partidos registrados.',
        );

        $division->delete();

        return back()->with('success', 'División eliminada correctamente.');
    }
}

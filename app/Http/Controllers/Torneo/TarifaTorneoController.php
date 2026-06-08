<?php

declare(strict_types=1);

namespace App\Http\Controllers\Torneo;

use App\Http\Controllers\Concerns\AutorizaTorneo;
use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Torneo\TarifaRequest;
use App\Http\Requests\Torneo\UpdateTarifaRequest;
use App\Models\DivisionTorneo;
use App\Models\TarifaTorneo;
use Illuminate\Http\RedirectResponse;

class TarifaTorneoController extends Controller
{
    use ResuelveColegio, AutorizaTorneo;

    public function store(TarifaRequest $request, int $divisionId): RedirectResponse
    {
        $division = DivisionTorneo::with('torneo')->findOrFail($divisionId);

        $this->autorizarTorneo($division->torneo);

        ['idRol' => $idRol, 'idFormato' => $idFormato, 'valorPago' => $valorPago] = $request->validated();

        TarifaTorneo::updateOrCreate(
            ['idDivision' => $division->idDivision, 'idRol' => $idRol, 'idFormato' => $idFormato],
            ['valorPago'  => $valorPago],
        );

        return back()->with('success', 'Tarifa guardada correctamente.');
    }

    public function update(UpdateTarifaRequest $request, int $id): RedirectResponse
    {
        $tarifa = TarifaTorneo::with('division.torneo')->findOrFail($id);

        $this->autorizarTorneo($tarifa->division->torneo);

        $tarifa->update($request->validated());

        return back()->with('success', 'Tarifa actualizada correctamente.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $tarifa = TarifaTorneo::with('division.torneo')->findOrFail($id);

        $this->autorizarTorneo($tarifa->division->torneo);

        $tarifa->delete();

        return back()->with('success', 'Tarifa eliminada correctamente.');
    }
}

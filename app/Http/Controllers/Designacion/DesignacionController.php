<?php

declare(strict_types=1);

namespace App\Http\Controllers\Designacion;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Models\Arbitro;
use App\Models\DisponibilidadArbitro;
use App\Support\SemanaNavegacion;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DesignacionController extends Controller
{
    use ResuelveColegio;

    /**
     * Vista de disponibilidad semanal de todos los árbitros del colegio.
     * Accesible para designador y ejecutivo (middleware permission:crear-designaciones).
     */
    public function disponibilidadGeneral(Request $request): View
    {
        $semana    = SemanaNavegacion::desde($request->query('semana'));
        $idColegio = $this->idColegioActivo();

        $arbitros = Arbitro::where('idColegio', $idColegio)
            ->where('estadoArbitro', 'activo')
            ->with([
                'usuario',
                'disponibilidades' => fn ($q) => $q->whereBetween(
                    'fechaDisponibilidad',
                    [$semana->lunes->toDateString(), $semana->domingo->toDateString()]
                ),
                'indisponibilidadesExtraordinarias' => fn ($q) => $q->whereBetween(
                    'fechaAfectada',
                    [$semana->lunes->toDateString(), $semana->domingo->toDateString()]
                ),
            ])
            ->orderBy('idArbitro')
            ->get();

        return view('disponibilidad.general', [
            'arbitros' => $arbitros,
            'semana'   => $semana,
            'franjas'  => DisponibilidadArbitro::getFranjas(),
        ]);
    }
}

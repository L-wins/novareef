<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Arbitro;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $idColegio = Auth::user()->idColegio;

        $arbitrosRegistrados = Arbitro::where('idColegio', $idColegio)->count();
        $arbitrosActivos     = Arbitro::where('idColegio', $idColegio)
                                       ->where('estadoArbitro', 'activo')
                                       ->count();
        $arbitrosProceso     = Arbitro::where('idColegio', $idColegio)
                                       ->where('estadoArbitro', 'proceso_ingreso')
                                       ->count();
        $totalUsuarios       = User::where('idColegio', $idColegio)->count();

        return view('dashboard', compact(
            'arbitrosRegistrados',
            'arbitrosActivos',
            'arbitrosProceso',
            'totalUsuarios',
        ));
    }
}

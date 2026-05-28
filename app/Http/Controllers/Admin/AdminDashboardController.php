<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Arbitro;
use App\Models\Colegio;
use App\Models\Suscripcion;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $totalColegios   = Colegio::count();
        $colegiosActivos = Colegio::where('estadoColegio', 'activo')->count();
        $colegiosTrial   = Suscripcion::where('estado', 'trial')->distinct('idColegio')->count('idColegio');
        $totalArbitros   = Arbitro::count();

        $ultimosColegios = Colegio::orderByDesc('created_at')->take(5)->get();

        return view('admin.dashboard', compact(
            'totalColegios',
            'colegiosActivos',
            'colegiosTrial',
            'totalArbitros',
            'ultimosColegios',
        ));
    }
}

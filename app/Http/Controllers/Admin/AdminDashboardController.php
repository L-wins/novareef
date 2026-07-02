<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminDashboardMetrics;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $metrics = AdminDashboardMetrics::cargar();

        return view('admin.dashboard', [
            'totalColegios'   => $metrics->totalColegios,
            'colegiosActivos' => $metrics->colegiosActivos,
            'colegiosTrial'   => $metrics->colegiosTrial,
            'totalArbitros'   => $metrics->totalArbitros,
            'ultimosColegios' => $metrics->ultimosColegios,
        ]);
    }
}

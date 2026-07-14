<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Services\DashboardService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly DashboardService $dashboards,
    ) {}

    /**
     * Dashboard personalizado por rol — cada uno tiene su propia vista y su
     * propio payload de datos, armado por DashboardService a partir de los
     * servicios de cada dominio. Sin lógica de negocio ni queries aquí.
     */
    public function index(): View
    {
        $user      = Auth::user();
        $idColegio = $user->idColegio;

        return match ($user->rolUsuario) {
            'ejecutivo'  => view('dashboard.ejecutivo', $this->dashboards->paraEjecutivo($idColegio)),
            'tesorero'   => view('dashboard.tesorero', $this->dashboards->paraTesorero($idColegio)),
            'designador' => view('dashboard.designador', $this->dashboards->paraDesignador($idColegio)),
            'sanciones'  => view('dashboard.sanciones', $this->dashboards->paraSanciones($idColegio)),
            'tecnico'    => view('dashboard.tecnico', $this->dashboards->paraTecnico($idColegio)),
            'arbitro'    => view('dashboard.arbitro', $this->dashboards->paraArbitro($this->arbitroAutenticado())),
            'veedor'     => view('dashboard.veedor', $this->dashboards->paraVeedor((int) $user->idUsuario)),
            default      => view('dashboard.generico'),
        };
    }
}

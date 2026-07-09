<?php

declare(strict_types=1);

namespace App\Http\Controllers\Arbitro;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Services\FinanzasService;
use Illuminate\View\View;

class EstadoCuentaArbitroController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly FinanzasService $finanzas,
    ) {}

    public function show(): View
    {
        $arbitro      = $this->arbitroAutenticado();
        $estadoCuenta = $this->finanzas->estadoCuentaArbitro($arbitro);

        return view('arbitros.estado-cuenta', compact('estadoCuenta'));
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finanza;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Services\ReporteFinanzasService;
use Illuminate\View\View;

class BalanceFinancieroController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly ReporteFinanzasService $reportes,
    ) {}

    public function index(): View
    {
        $balance = $this->reportes->balanceGeneral($this->idColegioActivo());

        return view('finanzas.balance.index', compact('balance'));
    }
}

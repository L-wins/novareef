<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finanza;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Services\FinanzasService;
use Illuminate\View\View;

class BalanceFinancieroController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly FinanzasService $finanzas,
    ) {}

    public function index(): View
    {
        $balance = $this->finanzas->balanceGeneral($this->idColegioActivo());

        return view('finanzas.balance.index', compact('balance'));
    }
}

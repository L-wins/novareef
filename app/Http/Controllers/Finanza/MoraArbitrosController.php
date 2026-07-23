<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finanza;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Services\BalanceFinanzasService;
use Illuminate\View\View;

/**
 * Listado de árbitros en mora (cuánto le deben al colegio, no cuánto se les
 * debe a ellos) — la misma agregación de balanceGeneral(), filtrada a
 * nosDebe > 0 y con antigüedad aproximada por bucket (estilo AR Aging Report).
 */
class MoraArbitrosController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly BalanceFinanzasService $reportes,
    ) {}

    public function index(): View
    {
        $idColegio = $this->idColegioActivo();

        $balance = $this->reportes->balanceGeneral($idColegio);
        $mora    = $this->reportes->moraDesdeBalance($balance);

        return view('finanzas.mora.index', compact('mora'));
    }
}

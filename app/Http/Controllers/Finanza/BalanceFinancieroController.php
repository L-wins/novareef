<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finanza;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finanza\StoreSaldoInicialRequest;
use App\Models\MovimientoFinanciero;
use App\Services\FinanzasService;
use App\Services\ReporteFinanzasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BalanceFinancieroController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly ReporteFinanzasService $reportes,
        private readonly FinanzasService $finanzas,
    ) {}

    public function index(): View
    {
        $idColegio = $this->idColegioActivo();

        $balance          = $this->reportes->balanceGeneral($idColegio);
        $bolsillos        = $this->reportes->bolsillosDesdeBalance($balance);
        $saldoPorMetodo   = $this->reportes->saldoPorMetodoPago($idColegio);

        $tieneSaldoInicial = MovimientoFinanciero::where('idColegio', $idColegio)
            ->where('categoria', MovimientoFinanciero::CATEGORIA_SALDO_INICIAL)
            ->exists();

        return view('finanzas.balance.index', compact('balance', 'bolsillos', 'saldoPorMetodo', 'tieneSaldoInicial'));
    }

    public function registrarSaldoInicial(StoreSaldoInicialRequest $request): RedirectResponse
    {
        $this->finanzas->registrarSaldoInicial($this->idColegioActivo(), $request->validated(), Auth::user());

        return redirect()
            ->route('finanzas.balance.index')
            ->with('success', 'Saldo registrado correctamente.');
    }
}

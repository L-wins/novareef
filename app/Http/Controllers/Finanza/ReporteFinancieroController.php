<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finanza;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finanza\ReporteFinancieroRequest;
use App\Services\FinanzasService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReporteFinancieroController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly FinanzasService $finanzas,
    ) {}

    public function index(ReporteFinancieroRequest $request): View
    {
        $desde = $request->validated('desde') ?: Carbon::now()->startOfMonth()->format('Y-m-d');
        $hasta = $request->validated('hasta') ?: Carbon::now()->endOfMonth()->format('Y-m-d');

        $reporte = $this->finanzas->reporte($this->idColegioActivo(), $desde, $hasta);

        return view('finanzas.reportes.index', compact('reporte', 'desde', 'hasta'));
    }
}

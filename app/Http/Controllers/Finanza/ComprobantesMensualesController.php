<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finanza;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Services\ReporteFinanzasService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Archivo de comprobantes (pagos de nómina y cobros de mensualidad) por mes
 * calendario — para encontrar y descargar un recibo sin tener que recordar
 * el UUID del lote.
 */
class ComprobantesMensualesController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly ReporteFinanzasService $reportes,
    ) {}

    public function index(Request $request): View
    {
        $idColegio = $this->idColegioActivo();

        $mesSolicitado = $request->string('mes')->toString();
        $mes           = preg_match('/^\d{4}-\d{2}$/', $mesSolicitado) ? $mesSolicitado : today()->format('Y-m');

        $comprobantes = $this->reportes->comprobantesDelMes($idColegio, $mes);

        return view('finanzas.comprobantes.index', compact('comprobantes', 'mes'));
    }
}

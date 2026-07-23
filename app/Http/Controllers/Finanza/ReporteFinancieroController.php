<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finanza;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finanza\ReporteFinancieroRequest;
use App\Models\Colegio;
use App\Services\BalanceFinanzasService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReporteFinancieroController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly BalanceFinanzasService $reportes,
    ) {}

    public function index(ReporteFinancieroRequest $request): View
    {
        [$desde, $hasta] = $this->rango($request);
        $idColegio       = $this->idColegioActivo();

        $reporte = $this->reportes->reporte($idColegio, $desde, $hasta);

        // La tendencia siempre muestra al menos 12 meses de contexto (aunque
        // el rango filtrado sea un solo mes) — termina en el mes de $hasta.
        $serieDesde = min(
            Carbon::parse($desde),
            Carbon::parse($hasta)->startOfMonth()->subMonths(11),
        )->toDateString();

        $serie = $this->reportes->serieMensual($idColegio, $serieDesde, $hasta);

        return view('finanzas.reportes.index', compact('reporte', 'serie', 'desde', 'hasta'));
    }

    /**
     * Exporta el reporte del rango filtrado a PDF (dompdf — mismo patrón que
     * el acta de designación).
     */
    public function pdf(ReporteFinancieroRequest $request): mixed
    {
        [$desde, $hasta] = $this->rango($request);
        $idColegio       = $this->idColegioActivo();

        $reporte = $this->reportes->reporte($idColegio, $desde, $hasta);

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('pdf.reporte-financiero', [
            'reporte'     => $reporte,
            'desde'       => $desde,
            'hasta'       => $hasta,
            'colegio'     => Colegio::find($idColegio),
            'generadoPor' => Auth::user(),
        ]);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("reporte-financiero-{$desde}-a-{$hasta}.pdf");
    }

    /** @return array{0: string, 1: string} */
    private function rango(ReporteFinancieroRequest $request): array
    {
        return [
            $request->validated('desde') ?: Carbon::now()->startOfMonth()->format('Y-m-d'),
            $request->validated('hasta') ?: Carbon::now()->endOfMonth()->format('Y-m-d'),
        ];
    }
}

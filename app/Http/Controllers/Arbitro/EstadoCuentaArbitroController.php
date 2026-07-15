<?php

declare(strict_types=1);

namespace App\Http\Controllers\Arbitro;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Services\ReporteFinanzasService;
use Illuminate\View\View;

class EstadoCuentaArbitroController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly ReporteFinanzasService $reportes,
    ) {}

    public function show(): View
    {
        $arbitro      = $this->arbitroAutenticado();
        $estadoCuenta = $this->reportes->estadoCuentaArbitro($arbitro);

        return view('arbitros.estado-cuenta', compact('estadoCuenta'));
    }

    /**
     * Comprobante PDF de un pago acumulado propio. Solo lotes cuyos
     * movimientos pertenecen al árbitro autenticado — un árbitro no puede
     * descargar comprobantes de otros.
     */
    public function comprobante(string $lote): mixed
    {
        $arbitro = $this->arbitroAutenticado();

        $datos = $this->reportes->datosComprobante($lote, (int) $arbitro->idColegio);

        abort_if($datos === null, 404);
        abort_unless((int) $datos['arbitro']->idArbitro === (int) $arbitro->idArbitro, 403);

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('pdf.comprobante-pago', [
            'datos'       => $datos,
            'idLotePago'  => $lote,
            'colegio'     => $arbitro->colegio,
            'generadoPor' => null,
        ]);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("comprobante-pago-{$datos['fecha']->format('Y-m-d')}-" . substr($lote, 0, 8) . '.pdf');
    }
}

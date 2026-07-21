<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finanza;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Models\Arbitro;
use App\Models\MovimientoFinanciero;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Matriz de cuotas del mes: cada árbitro activo del colegio contra el estado
 * de su mensualidad de ese mes (pagado/parcial/pendiente/no generada) — para
 * ver de un vistazo la cobertura del cobro, sin tener que revisar árbitro
 * por árbitro. "No generada" cubre tanto colegios sin cobro automático
 * configurado como meses en los que el tesorero aún no ha cobrado a mano.
 */
class CuotasMensualesController extends Controller
{
    use ResuelveColegio;

    public function index(Request $request): View
    {
        $idColegio = $this->idColegioActivo();

        $mesSolicitado = $request->string('mes')->toString();
        $mes           = preg_match('/^\d{4}-\d{2}$/', $mesSolicitado) ? $mesSolicitado : today()->format('Y-m');
        $fecha         = Carbon::createFromFormat('Y-m', $mes)->startOfMonth();

        $movimientos = MovimientoFinanciero::where('idColegio', $idColegio)
            ->where('categoria', MovimientoFinanciero::CATEGORIA_MENSUALIDAD)
            ->whereBetween('fechaMovimiento', [$fecha->toDateString(), $fecha->copy()->endOfMonth()->toDateString()])
            ->conTotalAbonado()
            ->get()
            ->keyBy('idArbitro');

        $filas = Arbitro::where('idColegio', $idColegio)
            ->whereNotIn('estadoArbitro', ['retirado'])
            ->with('usuario')
            ->get()
            ->map(function (Arbitro $arbitro) use ($movimientos): array {
                $movimiento = $movimientos->get($arbitro->idArbitro);

                return [
                    'arbitro'     => $arbitro,
                    'movimiento'  => $movimiento,
                    'estado'      => $movimiento?->estadoMovimiento,
                    'monto'       => $movimiento?->montoTotal,
                ];
            })
            ->sortBy(fn (array $f) => $f['arbitro']->usuario->nombreUsuario ?? '')
            ->values();

        return view('finanzas.cuotas.index', [
            'filas' => $filas,
            'mes'   => $mes,
            'fecha' => $fecha,
        ]);
    }
}

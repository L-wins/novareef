<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finanza;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finanza\StoreCobroMasivoRequest;
use App\Models\Arbitro;
use App\Models\MovimientoFinanciero;
use App\Services\FinanzasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CobroMasivoController extends Controller
{
    use ResuelveColegio;

    public function __construct(private readonly FinanzasService $finanzas) {}

    public function index(): View
    {
        $idColegio = $this->idColegioActivo();

        // orderBy vía join en vez de ->get()->sortBy() en PHP: el ordenamiento
        // lo hace el motor de BD (con índice), no una carga completa a memoria
        // para ordenar después — importa en colegios de ~300 árbitros.
        $arbitros = Arbitro::where('arbitros.idColegio', $idColegio)
            ->whereNotIn('arbitros.estadoArbitro', ['retirado'])
            ->join('usuarios', 'usuarios.idUsuario', '=', 'arbitros.idUsuario')
            ->with('usuario')
            ->orderBy('usuarios.nombreUsuario')
            ->select('arbitros.*')
            ->get();

        $categorias = [
            MovimientoFinanciero::CATEGORIA_MENSUALIDAD  => MovimientoFinanciero::ETIQUETAS_CATEGORIA[MovimientoFinanciero::CATEGORIA_MENSUALIDAD],
            MovimientoFinanciero::CATEGORIA_OTRO_INGRESO => MovimientoFinanciero::ETIQUETAS_CATEGORIA[MovimientoFinanciero::CATEGORIA_OTRO_INGRESO],
        ];

        return view('finanzas.cobro-masivo.index', compact('arbitros', 'categorias'));
    }

    public function store(StoreCobroMasivoRequest $request): RedirectResponse
    {
        try {
            $resultado = $this->finanzas->registrarCobroMasivo($this->idColegioActivo(), $request->validated(), Auth::user());
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('finanzas.balance.index')
            ->with('success', sprintf(
                'Cobro masivo registrado: %d movimiento(s) creado(s) (%d ya pagado(s)), %d omitido(s) por duplicado.',
                $resultado['totalCreados'],
                $resultado['totalPagados'],
                $resultado['totalOmitidos'],
            ));
    }
}

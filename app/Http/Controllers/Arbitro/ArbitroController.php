<?php

declare(strict_types=1);

namespace App\Http\Controllers\Arbitro;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Arbitro\ArchivarArbitroRequest;
use App\Http\Requests\Arbitro\StoreArbitroRequest;
use App\Http\Requests\Arbitro\ToggleEstadoArbitroRequest;
use App\Http\Requests\Arbitro\UpdateArbitroRequest;
use App\Models\Arbitro;
use App\Models\CategoriaArbitro;
use App\Models\EstadoArbitro;
use App\Services\ArbitroService;
use App\Services\EstadoCuentaArbitroService;
use App\Services\LimiteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ArbitroController extends Controller
{
    use ResuelveColegio;

    private const ORDENES_PERMITIDOS = [
        'nombre_asc'  => ['usuarios.nombreUsuario', 'asc'],
        'nombre_desc' => ['usuarios.nombreUsuario', 'desc'],
        'fecha_asc'   => ['arbitros.fechaIngresoColegio', 'asc'],
        'fecha_desc'  => ['arbitros.fechaIngresoColegio', 'desc'],
        'carnet_asc'  => ['arbitros.codigoCarnet', 'asc'],
    ];

    public function __construct(
        private readonly ArbitroService $arbitros,
        private readonly LimiteService  $limites,
        private readonly EstadoCuentaArbitroService $reportesFinanzas,
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $idColegio = $this->idColegioActivo();

        $query = Arbitro::with(['usuario', 'categoria', 'estado'])
            ->join('usuarios', 'usuarios.idUsuario', '=', 'arbitros.idUsuario')
            ->where('arbitros.idColegio', $idColegio)
            ->select('arbitros.*');

        if ($buscar = trim((string) $request->query('buscar', ''))) {
            $query->where(function ($q) use ($buscar): void {
                $q->where('usuarios.nombreUsuario', 'like', "%{$buscar}%")
                  ->orWhere('arbitros.numeroDocumento', 'like', "%{$buscar}%")
                  ->orWhere('arbitros.codigoCarnet', 'like', "%{$buscar}%");
            });
        }

        if ($estado = $request->query('estado')) {
            $query->where('arbitros.estadoArbitro', $estado);
        }

        if ($categoriaId = $request->query('categoria')) {
            $query->where('arbitros.idCategoria', (int) $categoriaId);
        }

        [$col, $dir] = self::ORDENES_PERMITIDOS[$request->query('orden', 'nombre_asc')]
            ?? self::ORDENES_PERMITIDOS['nombre_asc'];

        $arbitros   = $query->orderBy($col, $dir)->paginate(15)->withQueryString();
        $categorias = $this->categorias($idColegio);
        $estados    = $this->estados();

        $datos = compact('arbitros', 'categorias', 'estados') + [
            'limiteUsados'     => $this->limites->arbitrosActivos($idColegio),
            'limite'           => $this->limites->limiteArbitros($idColegio),
            'limitePorcentaje' => $this->limites->porcentajeUsoArbitros($idColegio),
        ];

        // auto-filter.js (modo AJAX): la lista/el contador se actualizan sin
        // recargar toda la página — ver resources/js/shared/auto-filter.js.
        if ($request->ajax()) {
            return response()->json([
                'regions' => [
                    'contador'   => view('arbitros.partials.contador', $datos)->render(),
                    'resultados' => view('arbitros.partials.resultados', $datos)->render(),
                ],
            ]);
        }

        return view('arbitros.index', $datos);
    }

    public function show(int $id): View
    {
        $arbitro = $this->arbitroDelColegio($id, [
            'usuario', 'categoria', 'colegio', 'documentos',
            'estado', 'historialEstados.usuarioCambio', 'historialEstados.estadoNuevoModel',
        ]);

        // Resumen financiero: solo se calcula si el usuario puede verlo — la
        // mayoría de roles con ver-arbitros (designador, técnico, sanciones)
        // no tienen ver-finanzas.
        $resumenFinanciero = null;
        if (Auth::user()->can('ver-finanzas')) {
            $resumenFinanciero = [
                'leDebemos' => $this->reportesFinanzas->saldoPendienteArbitro($arbitro),
                'nosDebe'   => $this->reportesFinanzas->saldoPorCobrarArbitro($arbitro),
            ];
        }

        return view('arbitros.show', [
            'arbitro'           => $arbitro,
            'estados'           => $this->estados(),
            'resumenFinanciero' => $resumenFinanciero,
        ]);
    }

    public function create(): View|RedirectResponse
    {
        $idColegio = $this->idColegioActivo();

        if (! $this->limites->puedeCrearArbitro($idColegio)) {
            return redirect()
                ->route('arbitros.index')
                ->with('error', $this->mensajeLimiteAlcanzado($idColegio));
        }

        return view('arbitros.create', [
            'categorias' => $this->categorias($idColegio),
        ]);
    }

    public function store(StoreArbitroRequest $request): RedirectResponse
    {
        $datos     = $request->validated();
        $idColegio = $this->idColegioActivo();

        // nombreColegio viene de la relación ya disponible en el modelo User.
        $nombreColegio = Auth::user()->colegio?->nombreColegio ?? 'NovaReef';

        try {
            $arbitro = $this->arbitros->registrar(
                idColegio:           $idColegio,
                nombreColegio:       $nombreColegio,
                urlAcceso:           config('app.url') . '/login',
                nombreUsuario:       $datos['nombreUsuario'],
                emailUsuario:        $datos['emailUsuario'],
                telefonoUsuario:     $datos['telefonoUsuario'] ?? null,
                idCategoria:         (int) $datos['idCategoria'],
                tipoDocumento:       $datos['tipoDocumento'],
                numeroDocumento:     $datos['numeroDocumento'],
                fechaIngresoColegio: $datos['fechaIngresoColegio'],
                lugarExpedicionCC:   $datos['lugarExpedicionCC'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('arbitros.show', $arbitro->idArbitro)
            ->with('success', "Árbitro registrado correctamente. Carné: {$arbitro->codigoCarnet}");
    }

    /** Mensaje consistente con el que lanza LimiteService::asegurarPuedeCrearArbitro(). */
    private function mensajeLimiteAlcanzado(int $idColegio): string
    {
        $limite = $this->limites->limiteArbitros($idColegio);

        return "Alcanzaste el límite de árbitros de tu plan actual ({$limite}). Actualiza tu plan para registrar más árbitros.";
    }

    public function edit(int $id): View
    {
        $arbitro = $this->arbitroDelColegio($id, ['usuario']);

        return view('arbitros.edit', [
            'arbitro'    => $arbitro,
            'categorias' => $this->categorias($arbitro->idColegio),
        ]);
    }

    public function update(UpdateArbitroRequest $request, int $id): RedirectResponse
    {
        $arbitro = $this->arbitroDelColegio($id, ['usuario']);

        $this->arbitros->actualizar($arbitro, $request->validated());

        return redirect()
            ->route('arbitros.show', $arbitro->idArbitro)
            ->with('success', 'Árbitro actualizado correctamente.');
    }

    // ── Gestión de estados ────────────────

    public function cambiarEstado(ToggleEstadoArbitroRequest $request, int $id): RedirectResponse
    {
        $arbitro = $this->arbitroDelColegio($id);
        $datos   = $request->validated();

        try {
            $this->arbitros->cambiarEstado(
                $arbitro,
                $datos['estadoNuevo'],
                $datos['motivo'] ?? null,
                $datos['fechaInicio'] ?? null,
                $datos['fechaFin'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Estado actualizado correctamente.');
    }

    public function archivar(ArchivarArbitroRequest $request, int $id): RedirectResponse
    {
        $arbitro = $this->arbitroDelColegio($id, ['usuario']);

        $this->arbitros->archivar($arbitro, $request->validated('motivo'));

        return redirect()
            ->route('arbitros.index')
            ->with('success', 'Árbitro archivado correctamente.');
    }

    public function restaurar(int $id): RedirectResponse
    {
        $arbitro = $this->arbitroDelColegio($id, ['usuario'], conEliminados: true);

        $this->arbitros->restaurar($arbitro);

        return redirect()
            ->route('arbitros.show', $arbitro->idArbitro)
            ->with('success', 'Árbitro restaurado correctamente.');
    }

    public function archivados(): View
    {
        $arbitros = Arbitro::onlyTrashed()
            ->with(['usuario', 'categoria'])
            ->where('idColegio', $this->idColegioActivo())
            ->orderByDesc('deleted_at')
            ->paginate(15)
            ->withQueryString();

        return view('arbitros.archivados', compact('arbitros'));
    }

    // ── Helpers privados ──────────────────

    /**
     * Resuelve un árbitro por ID dentro del colegio activo — centraliza el
     * filtro de tenant (`idColegio`) que antes se repetía en cada método
     * (show, edit, update, cambiarEstado, archivar, restaurar).
     *
     * @param  string[]  $with
     */
    private function arbitroDelColegio(int $id, array $with = [], bool $conEliminados = false): Arbitro
    {
        $query = $conEliminados ? Arbitro::withTrashed() : Arbitro::query();

        return $query->with($with)
            ->where('idColegio', $this->idColegioActivo())
            ->findOrFail($id);
    }

    private function categorias(int $idColegio): \Illuminate\Database\Eloquent\Collection
    {
        return CategoriaArbitro::where('idColegio', $idColegio)
            ->where('activa', true)
            ->orderBy('nombreCategoria')
            ->get();
    }

    private function estados(): \Illuminate\Database\Eloquent\Collection
    {
        return EstadoArbitro::where('esActivo', true)->orderBy('orden')->get();
    }
}

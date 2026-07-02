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
use App\Models\HistorialEstadoArbitro;
use App\Services\ArbitroService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
    ) {}

    public function index(Request $request): View
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

        return view('arbitros.index', compact('arbitros', 'categorias', 'estados'));
    }

    public function show(int $id): View
    {
        $arbitro = Arbitro::with([
                'usuario', 'categoria', 'colegio', 'documentos',
                'estado', 'historialEstados.usuarioCambio', 'historialEstados.estadoNuevoModel',
            ])
            ->where('idColegio', $this->idColegioActivo())
            ->findOrFail($id);

        return view('arbitros.show', [
            'arbitro' => $arbitro,
            'estados' => $this->estados(),
        ]);
    }

    public function create(): View
    {
        return view('arbitros.create', [
            'categorias' => $this->categorias($this->idColegioActivo()),
        ]);
    }

    public function store(StoreArbitroRequest $request): RedirectResponse
    {
        $datos     = $request->validated();
        $idColegio = $this->idColegioActivo();

        // nombreColegio viene de la relación ya disponible en el modelo User.
        $nombreColegio = Auth::user()->colegio?->nombreColegio ?? 'NovaReef';

        try {
            $arbitro = DB::transaction(function () use ($datos, $idColegio, $nombreColegio): Arbitro {
                $usuario = $this->arbitros->registrarConCredenciales(
                    idColegio:     $idColegio,
                    nombre:        $datos['nombreUsuario'],
                    email:         $datos['emailUsuario'],
                    telefono:      $datos['telefonoUsuario'] ?? '',
                    rol:           'arbitro',
                    nombreColegio: $nombreColegio,
                    urlAcceso:     config('app.url') . '/login',
                );

                return Arbitro::create([
                    'idUsuario'           => $usuario->idUsuario,
                    'idColegio'           => $idColegio,
                    'idCategoria'         => $datos['idCategoria'],
                    'tipoDocumento'       => $datos['tipoDocumento'],
                    'numeroDocumento'     => $datos['numeroDocumento'],
                    'fechaIngresoColegio' => $datos['fechaIngresoColegio'],
                    'lugarExpedicionCC'   => $datos['lugarExpedicionCC'] ?? null,
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['emailUsuario' => $e->getMessage()]);
        }

        return redirect()
            ->route('arbitros.show', $arbitro->idArbitro)
            ->with('success', "Árbitro registrado correctamente. Carné: {$arbitro->codigoCarnet}");
    }

    public function edit(int $id): View
    {
        $arbitro = Arbitro::with('usuario')
            ->where('idColegio', $this->idColegioActivo())
            ->findOrFail($id);

        return view('arbitros.edit', [
            'arbitro'    => $arbitro,
            'categorias' => $this->categorias($arbitro->idColegio),
        ]);
    }

    public function update(UpdateArbitroRequest $request, int $id): RedirectResponse
    {
        $arbitro = Arbitro::with('usuario')
            ->where('idColegio', $this->idColegioActivo())
            ->findOrFail($id);

        $datos = $request->validated();

        DB::transaction(function () use ($datos, $arbitro): void {
            $datosUsuario = [
                'nombreUsuario'   => $datos['nombreUsuario'],
                'emailUsuario'    => $datos['emailUsuario'],
                'telefonoUsuario' => $datos['telefonoUsuario'] ?? null,
            ];

            if (! empty($datos['passwordUsuario'])) {
                $datosUsuario['passwordUsuario'] = $datos['passwordUsuario'];
            }

            $arbitro->usuario->update($datosUsuario);

            $arbitro->update(
                collect($datos)->except(['nombreUsuario', 'emailUsuario', 'telefonoUsuario', 'passwordUsuario'])->toArray()
            );
        });

        return redirect()
            ->route('arbitros.show', $arbitro->idArbitro)
            ->with('success', 'Árbitro actualizado correctamente.');
    }

    // ── Gestión de estados ────────────────────────────────────────────────────

    public function toggleEstado(ToggleEstadoArbitroRequest $request, int $id): RedirectResponse
    {
        $arbitro = Arbitro::where('idArbitro', $id)
            ->where('idColegio', $this->idColegioActivo())
            ->firstOrFail();

        $datos = $request->validated();

        if ($arbitro->estadoArbitro === $datos['estadoNuevo']) {
            return back()->with('error', 'El árbitro ya tiene ese estado.');
        }

        DB::transaction(function () use ($arbitro, $datos): void {
            $this->registrarHistorial($arbitro, $datos['estadoNuevo'], $datos['motivo'] ?? null, $datos['fechaInicio'] ?? null, $datos['fechaFin'] ?? null);
            $arbitro->update(['estadoArbitro' => $datos['estadoNuevo']]);
        });

        return back()->with('success', 'Estado actualizado correctamente.');
    }

    public function archivar(ArchivarArbitroRequest $request, int $id): RedirectResponse
    {
        $arbitro = Arbitro::with('usuario')
            ->where('idArbitro', $id)
            ->where('idColegio', $this->idColegioActivo())
            ->firstOrFail();

        DB::transaction(function () use ($arbitro, $request): void {
            $this->registrarHistorial($arbitro, 'retirado', $request->validated('motivo'));
            $arbitro->update(['estadoArbitro' => 'retirado']);
            $arbitro->usuario?->update(['estadoUsuario' => 'inactivo']);
            $arbitro->delete();
        });

        return redirect()
            ->route('arbitros.index')
            ->with('success', 'Árbitro archivado correctamente.');
    }

    public function restaurar(int $id): RedirectResponse
    {
        $arbitro = Arbitro::withTrashed()
            ->with('usuario')
            ->where('idArbitro', $id)
            ->where('idColegio', $this->idColegioActivo())
            ->firstOrFail();

        DB::transaction(function () use ($arbitro): void {
            $arbitro->restore();
            $arbitro->update(['estadoArbitro' => 'inactivo']);
            $arbitro->usuario?->update(['estadoUsuario' => 'activo']);
            $this->registrarHistorial($arbitro, 'inactivo', 'Árbitro restaurado', estadoAnterior: 'retirado');
        });

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
            ->paginate(15);

        return view('arbitros.archivados', compact('arbitros'));
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

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

    /**
     * Centraliza la creación del historial — antes duplicado en toggleEstado, archivar y restaurar.
     */
    private function registrarHistorial(
        Arbitro $arbitro,
        string  $estadoNuevo,
        ?string $motivo        = null,
        ?string $fechaInicio   = null,
        ?string $fechaFin      = null,
        ?string $estadoAnterior = null,
    ): void {
        HistorialEstadoArbitro::create([
            'idArbitro'       => $arbitro->idArbitro,
            'idUsuarioCambio' => Auth::id(),
            'estadoAnterior'  => $estadoAnterior ?? $arbitro->estadoArbitro,
            'estadoNuevo'     => $estadoNuevo,
            'motivo'          => $motivo,
            'fechaInicio'     => $fechaInicio,
            'fechaFin'        => $fechaFin,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Arbitro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Arbitro\StoreArbitroRequest;
use App\Http\Requests\Arbitro\UpdateArbitroRequest;
use App\Models\Arbitro;
use App\Models\CategoriaArbitro;
use App\Models\Colegio;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ArbitroController extends Controller
{
    /** Roles con permisos administrativos sobre el módulo de árbitros. */
    private const ROLES_ADMIN = ['superadmin', 'ejecutivo'];

    /** Ciclo de estados aplicado por toggleEstado(). */
    private const CICLO_ESTADO = [
        'proceso_ingreso' => 'activo',
        'activo'          => 'suspendido',
        'suspendido'      => 'inactivo',
        'inactivo'        => 'proceso_ingreso',
    ];

    public function index(): View
    {
        $this->autorizarAdmin();

        $arbitros = Arbitro::with(['usuario', 'categoria'])
            ->where('idColegio', $this->idColegioActivo())
            ->orderByDesc('idArbitro')
            ->paginate(15);

        return view('arbitros.index', compact('arbitros'));
    }

    public function show(int $id): View
    {
        $arbitro = Arbitro::with(['usuario', 'categoria', 'colegio', 'documentos'])
            ->findOrFail($id);

        $this->autorizarAcceso($arbitro);

        return view('arbitros.show', compact('arbitro'));
    }

    public function create(): View
    {
        $this->autorizarAdmin();

        $categorias = $this->categoriasDisponibles($this->idColegioActivo());

        return view('arbitros.create', compact('categorias'));
    }

    public function store(StoreArbitroRequest $request): RedirectResponse
    {
        $this->autorizarAdmin();

        $datos     = $request->validated();
        $idColegio = $this->idColegioActivo();

        $arbitro = DB::transaction(function () use ($datos, $idColegio): Arbitro {
            // 1. Usuario con rol 'arbitro' (la contraseña se cifra vía cast 'hashed').
            $usuario = User::create([
                'idColegio'       => $idColegio,
                'nombreUsuario'   => $datos['nombreUsuario'],
                'emailUsuario'    => $datos['emailUsuario'],
                'passwordUsuario' => $datos['passwordUsuario'],
                'telefonoUsuario' => $datos['telefonoUsuario'] ?? null,
                'rolUsuario'      => 'arbitro',
                'estadoUsuario'   => 'activo',
            ]);

            // 2. Árbitro. codigoCarnet y estadoArbitro='proceso_ingreso' los genera
            //    el modelo Arbitro en su evento 'creating'; la limpieza de datos de
            //    vehículo la aplica el evento 'saving'.
            return Arbitro::create([
                'idUsuario'           => $usuario->idUsuario,
                'idColegio'           => $idColegio,
                'idCategoria'         => $datos['idCategoria'],
                'numeroDocumento'     => $datos['numeroDocumento'],
                'tipoDocumento'       => $datos['tipoDocumento'],
                'lugarExpedicionCC'   => $datos['lugarExpedicionCC'] ?? null,
                'pesoArbitro'         => $datos['pesoArbitro'] ?? null,
                'estaturaArbitro'     => $datos['estaturaArbitro'] ?? null,
                'rhArbitro'           => $datos['rhArbitro'] ?? null,
                'epsArbitro'          => $datos['epsArbitro'] ?? null,
                'profesionArbitro'    => $datos['profesionArbitro'] ?? null,
                'fechaIngresoColegio' => $datos['fechaIngresoColegio'] ?? null,
                'direccionArbitro'    => $datos['direccionArbitro'] ?? null,
                'barrioArbitro'       => $datos['barrioArbitro'] ?? null,
                'tieneVehiculo'       => $datos['tieneVehiculo'] ?? false,
                'tipoVehiculo'        => $datos['tipoVehiculo'] ?? null,
                'marcaVehiculo'       => $datos['marcaVehiculo'] ?? null,
                'placaVehiculo'       => $datos['placaVehiculo'] ?? null,
                'colorVehiculo'       => $datos['colorVehiculo'] ?? null,
            ]);
        });

        return redirect()
            ->route('arbitros.show', $arbitro->idArbitro)
            ->with('success', "Árbitro registrado correctamente. Carné: {$arbitro->codigoCarnet}");
    }

    public function edit(int $id): View
    {
        $arbitro = Arbitro::with('usuario')->findOrFail($id);

        $this->autorizarAcceso($arbitro);

        $categorias = $this->categoriasDisponibles($arbitro->idColegio);

        return view('arbitros.edit', compact('arbitro', 'categorias'));
    }

    public function update(UpdateArbitroRequest $request, int $id): RedirectResponse
    {
        $arbitro = Arbitro::with('usuario')->findOrFail($id);

        $this->autorizarAcceso($arbitro);

        $datos = $request->validated();

        DB::transaction(function () use ($datos, $arbitro): void {
            $datosUsuario = [
                'nombreUsuario'   => $datos['nombreUsuario'],
                'emailUsuario'    => $datos['emailUsuario'],
                'telefonoUsuario' => $datos['telefonoUsuario'] ?? null,
            ];

            // La contraseña solo se actualiza si se proporcionó una nueva.
            if (! empty($datos['passwordUsuario'])) {
                $datosUsuario['passwordUsuario'] = $datos['passwordUsuario'];
            }

            $arbitro->usuario->update($datosUsuario);

            $arbitro->update([
                'idCategoria'         => $datos['idCategoria'],
                'numeroDocumento'     => $datos['numeroDocumento'],
                'tipoDocumento'       => $datos['tipoDocumento'],
                'lugarExpedicionCC'   => $datos['lugarExpedicionCC'] ?? null,
                'pesoArbitro'         => $datos['pesoArbitro'] ?? null,
                'estaturaArbitro'     => $datos['estaturaArbitro'] ?? null,
                'rhArbitro'           => $datos['rhArbitro'] ?? null,
                'epsArbitro'          => $datos['epsArbitro'] ?? null,
                'profesionArbitro'    => $datos['profesionArbitro'] ?? null,
                'fechaIngresoColegio' => $datos['fechaIngresoColegio'] ?? null,
                'direccionArbitro'    => $datos['direccionArbitro'] ?? null,
                'barrioArbitro'       => $datos['barrioArbitro'] ?? null,
                'tieneVehiculo'       => $datos['tieneVehiculo'] ?? false,
                'tipoVehiculo'        => $datos['tipoVehiculo'] ?? null,
                'marcaVehiculo'       => $datos['marcaVehiculo'] ?? null,
                'placaVehiculo'       => $datos['placaVehiculo'] ?? null,
                'colorVehiculo'       => $datos['colorVehiculo'] ?? null,
            ]);
        });

        return redirect()
            ->route('arbitros.show', $arbitro->idArbitro)
            ->with('success', 'Árbitro actualizado correctamente.');
    }

    public function toggleEstado(int $id): RedirectResponse
    {
        $arbitro = Arbitro::findOrFail($id);

        $this->autorizarAdmin();

        $nuevoEstado = self::CICLO_ESTADO[$arbitro->estadoArbitro] ?? 'proceso_ingreso';
        $arbitro->estadoArbitro = $nuevoEstado;
        $arbitro->save();

        $etiqueta = ucfirst(str_replace('_', ' ', $nuevoEstado));

        return back()->with('success', "Estado actualizado a «{$etiqueta}».");
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Colegio del contexto del usuario autenticado. Si el usuario no tiene
     * colegio asignado (p. ej. un superadmin global), usa el primer colegio.
     */
    private function idColegioActivo(): int
    {
        return (int) (Auth::user()->idColegio ?? Colegio::query()->min('idColegio'));
    }

    private function categoriasDisponibles(int $idColegio)
    {
        return CategoriaArbitro::where('idColegio', $idColegio)
            ->where('activa', true)
            ->orderBy('nombreCategoria')
            ->get();
    }

    private function esAdmin(): bool
    {
        return in_array(Auth::user()->rolUsuario, self::ROLES_ADMIN, true);
    }

    private function autorizarAdmin(): void
    {
        abort_unless($this->esAdmin(), 403, 'No tienes permisos para gestionar árbitros.');
    }

    /**
     * Permite el acceso a un árbitro si el usuario es administrador o es el
     * propio árbitro (su registro de usuario).
     */
    private function autorizarAcceso(Arbitro $arbitro): void
    {
        $esPropietario = (int) $arbitro->idUsuario === (int) Auth::id();

        abort_unless(
            $this->esAdmin() || $esPropietario,
            403,
            'No tienes permisos para acceder a este árbitro.'
        );
    }
}

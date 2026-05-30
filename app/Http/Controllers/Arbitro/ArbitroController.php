<?php

declare(strict_types=1);

namespace App\Http\Controllers\Arbitro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Arbitro\StoreArbitroRequest;
use App\Http\Requests\Arbitro\UpdateArbitroRequest;
use App\Mail\CredencialesColegioMail;
use App\Models\Arbitro;
use App\Models\CategoriaArbitro;
use App\Models\EstadoArbitro;
use App\Models\HistorialEstadoArbitro;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ArbitroController extends Controller
{
    private const ORDENES_PERMITIDOS = [
        'nombre_asc'  => ['usuarios.nombreUsuario', 'asc'],
        'nombre_desc' => ['usuarios.nombreUsuario', 'desc'],
        'fecha_asc'   => ['arbitros.fechaIngresoColegio', 'asc'],
        'fecha_desc'  => ['arbitros.fechaIngresoColegio', 'desc'],
        'carnet_asc'  => ['arbitros.codigoCarnet', 'asc'],
    ];

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

        $ordenKey       = (string) $request->query('orden', 'nombre_asc');
        [$col, $dir]    = self::ORDENES_PERMITIDOS[$ordenKey] ?? self::ORDENES_PERMITIDOS['nombre_asc'];
        $query->orderBy($col, $dir);

        $arbitros   = $query->paginate(15)->withQueryString();
        $categorias = $this->categoriasDisponibles($idColegio);
        $estados    = $this->estadosDisponibles();

        return view('arbitros.index', compact('arbitros', 'categorias', 'estados'));
    }

    public function show(int $id): View
    {
        $arbitro = Arbitro::with([
                'usuario',
                'categoria',
                'colegio',
                'documentos',
                'estado',
                'historialEstados.usuarioCambio',
                'historialEstados.estadoNuevoModel',
            ])
            ->findOrFail($id);

        $this->autorizarAcceso($arbitro);

        $estados = $this->estadosDisponibles();

        return view('arbitros.show', compact('arbitro', 'estados'));
    }

    public function create(): View
    {
        $categorias = $this->categoriasDisponibles($this->idColegioActivo());

        return view('arbitros.create', compact('categorias'));
    }

    public function store(StoreArbitroRequest $request): RedirectResponse
    {
        $datos         = $request->validated();
        $idColegio     = $this->idColegioActivo();
        $password      = Str::password(12);
        $mailData      = null;
        $nombreColegio = DB::table('colegios')->where('idColegio', $idColegio)->value('nombreColegio') ?? 'NovaReef';

        $arbitro = DB::transaction(function () use ($datos, $idColegio, $password, &$mailData): Arbitro {
            $usuario = User::create([
                'idColegio'            => $idColegio,
                'nombreUsuario'        => $datos['nombreUsuario'],
                'emailUsuario'         => $datos['emailUsuario'],
                'passwordUsuario'      => $password,
                'telefonoUsuario'      => $datos['telefonoUsuario'],
                'rolUsuario'           => 'arbitro',
                'estadoUsuario'        => 'activo',
                'must_change_password' => true,
            ]);

            $usuario->assignRole('arbitro');

            $mailData = $usuario->emailUsuario;

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

        try {
            Mail::to($mailData)->send(
                new CredencialesColegioMail(
                    $nombreColegio,
                    config('app.url') . '/login',
                    $mailData,
                    $password,
                )
            );
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar email de credenciales al árbitro', [
                'email' => $mailData,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('arbitros.show', $arbitro->idArbitro)
            ->with('success', "Árbitro registrado correctamente. Carné: {$arbitro->codigoCarnet}");
    }

    public function edit(int $id): View
    {
        $arbitro = Arbitro::with('usuario')->findOrFail($id);

        abort_unless((int) $arbitro->idColegio === $this->idColegioActivo(), 403);

        $categorias = $this->categoriasDisponibles($arbitro->idColegio);

        return view('arbitros.edit', compact('arbitro', 'categorias'));
    }

    public function update(UpdateArbitroRequest $request, int $id): RedirectResponse
    {
        $arbitro = Arbitro::with('usuario')->findOrFail($id);

        abort_unless((int) $arbitro->idColegio === $this->idColegioActivo(), 403);

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

    public function toggleEstado(Request $request, int $id): RedirectResponse
    {
        $arbitro = Arbitro::where('idArbitro', $id)
            ->where('idColegio', $this->idColegioActivo())
            ->firstOrFail();

        $datos = $request->validate([
            'estadoNuevo' => ['required', 'exists:estados_arbitro,nombre'],
            'motivo'      => ['nullable', 'string', 'max:500', 'required_if:estadoNuevo,suspendido', 'required_if:estadoNuevo,retirado'],
            'fechaInicio' => ['nullable', 'date', 'required_if:estadoNuevo,suspendido'],
            'fechaFin'    => ['nullable', 'date', 'after:fechaInicio'],
        ], [
            'estadoNuevo.required'    => 'Debes seleccionar un estado.',
            'estadoNuevo.exists'      => 'El estado seleccionado no es válido.',
            'motivo.required_if'      => 'El motivo es obligatorio para este estado.',
            'fechaInicio.required_if' => 'La fecha de inicio es obligatoria para suspensiones.',
            'fechaFin.after'          => 'La fecha de fin debe ser posterior a la fecha de inicio.',
        ]);

        $estadoAnterior = $arbitro->estadoArbitro;

        if ($estadoAnterior === $datos['estadoNuevo']) {
            return back()->with('error', 'El árbitro ya tiene ese estado.');
        }

        DB::transaction(function () use ($arbitro, $estadoAnterior, $datos): void {
            HistorialEstadoArbitro::create([
                'idArbitro'       => $arbitro->idArbitro,
                'idUsuarioCambio' => Auth::user()->idUsuario,
                'estadoAnterior'  => $estadoAnterior,
                'estadoNuevo'     => $datos['estadoNuevo'],
                'motivo'          => $datos['motivo'] ?? null,
                'fechaInicio'     => $datos['fechaInicio'] ?? null,
                'fechaFin'        => $datos['fechaFin'] ?? null,
            ]);

            $arbitro->update(['estadoArbitro' => $datos['estadoNuevo']]);
        });

        return back()->with('success', 'Estado actualizado correctamente.');
    }

    // ── Archivado (soft delete) ──────────────────────────────────────────────

    public function archivar(Request $request, int $id): RedirectResponse
    {
        $arbitro = Arbitro::with('usuario')
            ->where('idArbitro', $id)
            ->where('idColegio', $this->idColegioActivo())
            ->firstOrFail();

        $datos = $request->validate([
            'motivo' => ['required', 'string', 'max:150'],
        ], [
            'motivo.required' => 'Debes indicar el motivo del archivado.',
            'motivo.max'      => 'El motivo no puede superar los 150 caracteres.',
        ]);

        $estadoAnterior = $arbitro->estadoArbitro;

        DB::transaction(function () use ($arbitro, $estadoAnterior, $datos): void {
            HistorialEstadoArbitro::create([
                'idArbitro'       => $arbitro->idArbitro,
                'idUsuarioCambio' => Auth::user()->idUsuario,
                'estadoAnterior'  => $estadoAnterior,
                'estadoNuevo'     => 'retirado',
                'motivo'          => $datos['motivo'],
            ]);

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

            HistorialEstadoArbitro::create([
                'idArbitro'       => $arbitro->idArbitro,
                'idUsuarioCambio' => Auth::user()->idUsuario,
                'estadoAnterior'  => 'retirado',
                'estadoNuevo'     => 'inactivo',
                'motivo'          => 'Árbitro restaurado',
            ]);
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

    // ── Foto de perfil ───────────────────────────────────────────────────────

    public function subirFoto(Request $request, int $id): RedirectResponse
    {
        $arbitro = Arbitro::findOrFail($id);

        $this->autorizarFoto($arbitro);

        $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp,bmp,svg', 'max:5120'],
        ], [
            'foto.required' => 'Debes seleccionar una imagen.',
            'foto.image'    => 'El archivo debe ser una imagen.',
            'foto.mimes'    => 'Formatos permitidos: jpg, jpeg, png, gif, webp, bmp, svg.',
            'foto.max'      => 'La imagen no puede superar 5 MB.',
        ]);

        if ($arbitro->fotoPerfil && Storage::disk('public')->exists($arbitro->fotoPerfil)) {
            Storage::disk('public')->delete($arbitro->fotoPerfil);
        }

        $ruta = $request->file('foto')->store('fotos-arbitros', 'public');
        $arbitro->update(['fotoPerfil' => $ruta]);

        return back()->with('success', 'Foto de perfil actualizada correctamente.');
    }

    public function eliminarFoto(int $id): RedirectResponse
    {
        $arbitro = Arbitro::findOrFail($id);

        abort_unless((int) $arbitro->idUsuario === (int) Auth::id(), 403);

        if ($arbitro->fotoPerfil && Storage::disk('public')->exists($arbitro->fotoPerfil)) {
            Storage::disk('public')->delete($arbitro->fotoPerfil);
        }

        $arbitro->update(['fotoPerfil' => null]);

        return back()->with('success', 'Foto de perfil eliminada.');
    }

    // ── Perfil del árbitro autenticado ───────────────────────────────────────

    public function miPerfil(): View
    {
        $arbitro = Arbitro::with(['usuario', 'categoria', 'colegio', 'estado', 'documentos'])
            ->where('idUsuario', Auth::id())
            ->firstOrFail();

        return view('arbitros.mi-perfil', compact('arbitro'));
    }

    public function actualizarMiPerfil(Request $request): RedirectResponse
    {
        $arbitro = Arbitro::where('idUsuario', Auth::id())->firstOrFail();

        $datos = $request->validate([
            'telefonoUsuario'  => ['nullable', 'string', 'max:20'],
            'direccionArbitro' => ['nullable', 'string', 'max:255'],
            'barrioArbitro'    => ['nullable', 'string', 'max:100'],
            'epsArbitro'       => ['nullable', 'string', 'max:100'],
            'pesoArbitro'      => ['nullable', 'numeric', 'min:30', 'max:200'],
            'estaturaArbitro'  => ['nullable', 'numeric', 'min:1.00', 'max:2.50'],
            'tieneVehiculo'    => ['boolean'],
            'tipoVehiculo'     => ['nullable', 'required_if:tieneVehiculo,1', 'in:carro,moto,ambos'],
            'marcaVehiculo'    => ['nullable', 'required_if:tieneVehiculo,1', 'string', 'max:50'],
            'placaVehiculo'    => ['nullable', 'required_if:tieneVehiculo,1', 'string', 'max:20'],
            'colorVehiculo'    => ['nullable', 'required_if:tieneVehiculo,1', 'string', 'max:30'],
        ]);

        DB::transaction(function () use ($arbitro, $datos, $request): void {
            if (array_key_exists('telefonoUsuario', $datos)) {
                $arbitro->usuario->update(['telefonoUsuario' => $datos['telefonoUsuario']]);
            }

            $arbitro->update([
                'direccionArbitro' => $datos['direccionArbitro'] ?? null,
                'barrioArbitro'    => $datos['barrioArbitro'] ?? null,
                'epsArbitro'       => $datos['epsArbitro'] ?? null,
                'pesoArbitro'      => $datos['pesoArbitro'] ?? null,
                'estaturaArbitro'  => $datos['estaturaArbitro'] ?? null,
                'tieneVehiculo'    => $request->boolean('tieneVehiculo'),
                'tipoVehiculo'     => $datos['tipoVehiculo'] ?? null,
                'marcaVehiculo'    => $datos['marcaVehiculo'] ?? null,
                'placaVehiculo'    => $datos['placaVehiculo'] ?? null,
                'colorVehiculo'    => $datos['colorVehiculo'] ?? null,
            ]);
        });

        return redirect()
            ->route('arbitros.mi-perfil')
            ->with('success', 'Perfil actualizado correctamente.');
    }

    public function completarPerfil(): View
    {
        $arbitro = Arbitro::where('idUsuario', Auth::id())->firstOrFail();

        return view('arbitros.completar-perfil', compact('arbitro'));
    }

    public function guardarPerfil(Request $request): RedirectResponse
    {
        $arbitro = Arbitro::where('idUsuario', Auth::id())->firstOrFail();

        $datos = $request->validate([
            'lugarExpedicionCC' => ['nullable', 'string', 'max:100'],
            'pesoArbitro'       => ['nullable', 'numeric', 'min:30', 'max:200'],
            'estaturaArbitro'   => ['nullable', 'numeric', 'min:1.00', 'max:2.50'],
            'rhArbitro'         => ['nullable', 'string', 'max:5'],
            'epsArbitro'        => ['nullable', 'string', 'max:100'],
            'profesionArbitro'  => ['nullable', 'string', 'max:100'],
            'direccionArbitro'  => ['nullable', 'string', 'max:255'],
            'barrioArbitro'     => ['nullable', 'string', 'max:100'],
            'tieneVehiculo'     => ['boolean'],
            'tipoVehiculo'      => ['nullable', 'required_if:tieneVehiculo,true', 'in:carro,moto,ambos'],
            'marcaVehiculo'     => ['nullable', 'required_if:tieneVehiculo,true', 'string', 'max:50'],
            'placaVehiculo'     => ['nullable', 'required_if:tieneVehiculo,true', 'string', 'max:20'],
            'colorVehiculo'     => ['nullable', 'required_if:tieneVehiculo,true', 'string', 'max:30'],
        ]);

        $arbitro->update(array_merge($datos, [
            'tieneVehiculo' => $request->boolean('tieneVehiculo'),
        ]));

        return redirect()
            ->route('dashboard')
            ->with('success', 'Perfil completado correctamente. ¡Bienvenido a NovaReef!');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function idColegioActivo(): int
    {
        $idColegio = Auth::user()->idColegio;

        abort_if($idColegio === null, 403, 'Tu cuenta no tiene un colegio asignado.');

        return (int) $idColegio;
    }

    private function categoriasDisponibles(int $idColegio): \Illuminate\Database\Eloquent\Collection
    {
        return CategoriaArbitro::where('idColegio', $idColegio)
            ->where('activa', true)
            ->orderBy('nombreCategoria')
            ->get();
    }

    private function estadosDisponibles(): \Illuminate\Database\Eloquent\Collection
    {
        return EstadoArbitro::where('esActivo', true)
            ->orderBy('orden')
            ->get();
    }

    private function autorizarAcceso(Arbitro $arbitro): void
    {
        $esPropietario = (int) $arbitro->idUsuario === (int) Auth::id();
        $mismoColegio  = (int) $arbitro->idColegio === $this->idColegioActivo();

        abort_unless($mismoColegio || $esPropietario, 403, 'No tienes acceso a este árbitro.');
    }

    private function autorizarFoto(Arbitro $arbitro): void
    {
        $esPropietario = (int) $arbitro->idUsuario === (int) Auth::id();
        $puedeEditar   = (int) $arbitro->idColegio === $this->idColegioActivo()
            && Auth::user()->can('editar-arbitros');

        abort_unless($esPropietario || $puedeEditar, 403);
    }
}

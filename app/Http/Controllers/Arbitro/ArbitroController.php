<?php

declare(strict_types=1);

namespace App\Http\Controllers\Arbitro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Arbitro\StoreArbitroRequest;
use App\Http\Requests\Arbitro\UpdateArbitroRequest;
use App\Mail\CredencialesColegioMail;
use App\Models\Arbitro;
use App\Models\CategoriaArbitro;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ArbitroController extends Controller
{
    private const CICLO_ESTADO = [
        'proceso_ingreso' => 'activo',
        'activo'          => 'inactivo',
        'inactivo'        => 'suspendido',
        'suspendido'      => 'retirado',
        'retirado'        => 'proceso_ingreso',
    ];

    public function index(Request $request): View
    {
        $idColegio = $this->idColegioActivo();

        $query = Arbitro::with(['usuario', 'categoria'])
            ->where('idColegio', $idColegio)
            ->orderByDesc('idArbitro');

        if ($estado = $request->query('estado')) {
            $query->where('estadoArbitro', $estado);
        }

        if ($categoriaId = $request->query('categoria')) {
            $query->where('idCategoria', (int) $categoriaId);
        }

        $arbitros   = $query->paginate(15)->withQueryString();
        $categorias = $this->categoriasDisponibles($idColegio);

        return view('arbitros.index', compact('arbitros', 'categorias'));
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

    public function toggleEstado(int $id): RedirectResponse
    {
        $arbitro = Arbitro::findOrFail($id);

        abort_unless((int) $arbitro->idColegio === $this->idColegioActivo(), 403);

        $nuevoEstado            = self::CICLO_ESTADO[$arbitro->estadoArbitro] ?? 'proceso_ingreso';
        $arbitro->estadoArbitro = $nuevoEstado;
        $arbitro->save();

        $etiqueta = ucfirst(str_replace('_', ' ', $nuevoEstado));

        return back()->with('success', "Estado actualizado a «{$etiqueta}».");
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

    private function autorizarAcceso(Arbitro $arbitro): void
    {
        $esPropietario = (int) $arbitro->idUsuario === (int) Auth::id();
        $mismoColegio  = (int) $arbitro->idColegio === $this->idColegioActivo();

        abort_unless($mismoColegio || $esPropietario, 403, 'No tienes acceso a este árbitro.');
    }
}

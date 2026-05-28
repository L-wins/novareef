<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\CredencialesColegioMail;
use App\Models\Arbitro;
use App\Models\Colegio;
use App\Models\Plan;
use App\Models\Suscripcion;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminColegioController extends Controller
{
    public function index(Request $request): View
    {
        $query = Colegio::with(['suscripcionActiva.plan'])
            ->withCount('arbitros')
            ->orderBy('nombreColegio');

        if ($search = trim((string) $request->input('q', ''))) {
            $query->where(function ($q) use ($search): void {
                $q->where('nombreColegio', 'like', "%{$search}%")
                  ->orWhere('codigoColegio', 'like', "%{$search}%");
            });
        }

        $colegios = $query->paginate(15)->withQueryString();

        return view('admin.colegios.index', compact('colegios'));
    }

    public function show(int $id): View
    {
        $colegio = Colegio::with(['suscripcionActiva.plan'])->findOrFail($id);

        $admin = User::where('idColegio', $id)
            ->where('rolUsuario', 'ejecutivo')
            ->orderByDesc('created_at')
            ->first();

        $totalArbitros   = Arbitro::where('idColegio', $id)->count();
        $arbitrosActivos = Arbitro::where('idColegio', $id)->where('estadoArbitro', 'activo')->count();
        $arbitrosProceso = Arbitro::where('idColegio', $id)->where('estadoArbitro', 'proceso_ingreso')->count();

        return view('admin.colegios.show', compact(
            'colegio',
            'admin',
            'totalArbitros',
            'arbitrosActivos',
            'arbitrosProceso',
        ));
    }

    public function create(): View
    {
        $planes = Plan::where('esActivo', true)->orderBy('orden')->get();

        return view('admin.colegios.create', compact('planes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->storeRules(), $this->storeMessages());

        $mailData = null;

        DB::transaction(function () use ($validated, &$mailData): void {
            $tenantId = $this->buildTenantId($validated['codigoColegio']);

            DB::table('tenants')->insertOrIgnore([
                'id'         => $tenantId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $colegioData = collect($validated)
                ->except(['idPlan', 'nombreAdmin', 'emailAdmin'])
                ->all();

            $colegio = Colegio::create(['tenantId' => $tenantId] + $colegioData);

            $plan   = Plan::findOrFail($validated['idPlan']);
            $inicio = today();
            $vence  = $plan->periodicidad === 'anual'
                ? $inicio->copy()->addYear()
                : $inicio->copy()->addMonth();

            Suscripcion::create([
                'idColegio'        => $colegio->idColegio,
                'idPlan'           => $plan->idPlan,
                'fechaInicio'      => $inicio,
                'fechaVencimiento' => $vence,
                'estado'           => 'activa',
            ]);

            $password = Str::password(12);

            $usuario = User::create([
                'idColegio'            => $colegio->idColegio,
                'nombreUsuario'        => $validated['nombreAdmin'],
                'emailUsuario'         => $validated['emailAdmin'],
                'passwordUsuario'      => $password,
                'rolUsuario'           => 'ejecutivo',
                'estadoUsuario'        => 'activo',
                'must_change_password' => true,
            ]);

            $usuario->assignRole('ejecutivo');

            $mailData = [
                'email'    => $validated['emailAdmin'],
                'colegio'  => $colegio->nombreColegio,
                'url'      => 'https://' . $tenantId . '.novareef.com',
                'password' => $password,
            ];
        });

        try {
            Mail::to($mailData['email'])->send(
                new CredencialesColegioMail(
                    $mailData['colegio'],
                    $mailData['url'],
                    $mailData['email'],
                    $mailData['password'],
                )
            );
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar email de credenciales', [
                'email' => $mailData['email'],
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('admin.colegios.index')
            ->with('success', 'Colegio registrado correctamente. Se enviaron las credenciales al correo del administrador.');
    }

    public function edit(int $id): View
    {
        $colegio = Colegio::with(['suscripcionActiva.plan'])->findOrFail($id);

        return view('admin.colegios.edit', compact('colegio'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $colegio   = Colegio::findOrFail($id);
        $validated = $request->validate($this->rules($id), $this->messages());

        $colegio->update($validated);

        return redirect()
            ->route('admin.colegios.show', $id)
            ->with('success', 'Colegio actualizado correctamente.');
    }

    public function toggleEstado(Request $request, int $id): RedirectResponse
    {
        $colegio     = Colegio::findOrFail($id);
        $nuevoEstado = $request->input('estado');

        if (in_array($nuevoEstado, ['activo', 'suspendido', 'inactivo'], true)) {
            $colegio->estadoColegio = $nuevoEstado;
        } else {
            $colegio->estadoColegio = $colegio->estadoColegio === 'activo' ? 'suspendido' : 'activo';
        }

        $colegio->save();

        $labels = ['activo' => 'activado', 'suspendido' => 'suspendido', 'inactivo' => 'marcado como inactivo'];
        $accion = $labels[$colegio->estadoColegio] ?? 'actualizado';

        return back()->with('success', "Colegio {$accion} correctamente.");
    }

    // ── helpers privados ─────────────────────────────────────────────────────

    private function storeRules(): array
    {
        return [
            'nombreColegio'       => ['required', 'string', 'max:255'],
            'codigoColegio'       => ['required', 'string', 'max:20', Rule::unique('colegios', 'codigoColegio')],
            'emailColegio'        => ['required', 'email', 'max:255'],
            'telefonoColegio'     => ['nullable', 'string', 'max:20'],
            'direccionColegio'    => ['nullable', 'string'],
            'ciudadColegio'       => ['nullable', 'string', 'max:100'],
            'departamentoColegio' => ['nullable', 'string', 'max:100'],
            'paisColegio'         => ['required', 'string', 'max:100'],
            'logoColegio'         => ['nullable', 'url', 'max:500'],
            'idPlan'              => ['required', 'integer', Rule::exists('planes', 'idPlan')],
            'nombreAdmin'         => ['required', 'string', 'max:150'],
            'emailAdmin'          => ['required', 'email', 'max:255', Rule::unique('usuarios', 'emailUsuario')],
        ];
    }

    private function storeMessages(): array
    {
        return array_merge($this->messages(), [
            'idPlan.required'      => 'Debes seleccionar un plan de suscripción.',
            'idPlan.exists'        => 'El plan seleccionado no existe.',
            'nombreAdmin.required' => 'El nombre del administrador es obligatorio.',
            'nombreAdmin.max'      => 'El nombre del administrador no puede superar 150 caracteres.',
            'emailAdmin.required'  => 'El correo del administrador es obligatorio.',
            'emailAdmin.email'     => 'El correo del administrador no es válido.',
            'emailAdmin.unique'    => 'Este correo ya está registrado como usuario en la plataforma.',
        ]);
    }

    private function rules(?int $ignoreId = null): array
    {
        return [
            'nombreColegio'       => ['required', 'string', 'max:255'],
            'codigoColegio'       => [
                'required', 'string', 'max:20',
                Rule::unique('colegios', 'codigoColegio')->ignore($ignoreId, 'idColegio'),
            ],
            'emailColegio'        => ['required', 'email', 'max:255'],
            'telefonoColegio'     => ['nullable', 'string', 'max:20'],
            'direccionColegio'    => ['nullable', 'string'],
            'ciudadColegio'       => ['nullable', 'string', 'max:100'],
            'departamentoColegio' => ['nullable', 'string', 'max:100'],
            'paisColegio'         => ['required', 'string', 'max:100'],
            'logoColegio'         => ['nullable', 'url', 'max:500'],
        ];
    }

    private function messages(): array
    {
        return [
            'nombreColegio.required'  => 'El nombre del colegio es obligatorio.',
            'nombreColegio.max'       => 'El nombre no puede superar 255 caracteres.',
            'codigoColegio.required'  => 'El código del colegio es obligatorio.',
            'codigoColegio.max'       => 'El código no puede superar 20 caracteres.',
            'codigoColegio.unique'    => 'Este código ya está en uso.',
            'emailColegio.required'   => 'El correo electrónico es obligatorio.',
            'emailColegio.email'      => 'Ingresa un correo electrónico válido.',
            'emailColegio.max'        => 'El correo no puede superar 255 caracteres.',
            'telefonoColegio.max'     => 'El teléfono no puede superar 20 caracteres.',
            'ciudadColegio.max'       => 'La ciudad no puede superar 100 caracteres.',
            'departamentoColegio.max' => 'El departamento no puede superar 100 caracteres.',
            'paisColegio.required'    => 'El país es obligatorio.',
            'paisColegio.max'         => 'El país no puede superar 100 caracteres.',
            'logoColegio.url'         => 'El logo debe ser una URL válida.',
            'logoColegio.max'         => 'La URL del logo no puede superar 500 caracteres.',
        ];
    }

    private function buildTenantId(string $codigo): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($codigo)));
    }
}

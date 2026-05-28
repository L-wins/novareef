<?php

declare(strict_types=1);

namespace App\Http\Controllers\Colegio;

use App\Http\Controllers\Controller;
use App\Mail\CredencialesColegioMail;
use App\Models\Colegio;
use App\Models\Plan;
use App\Models\Suscripcion;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ColegioController extends Controller
{
    public function index(): View
    {
        $colegios = Colegio::orderBy('nombreColegio')->get();

        return view('colegios.index', compact('colegios'));
    }

    public function show(int $id): View
    {
        $colegio = Colegio::findOrFail($id);

        return view('colegios.show', compact('colegio'));
    }

    public function create(): View
    {
        $planes = Plan::where('esActivo', true)->orderBy('orden')->get();

        return view('colegios.create', compact('planes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->storeRules(), $this->storeMessages());

        DB::transaction(function () use ($validated): void {
            // 1. Crear tenant
            $tenantId = $this->buildTenantId($validated['codigoColegio']);

            DB::table('tenants')->insertOrIgnore([
                'id'         => $tenantId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. Crear colegio
            $colegioData = collect($validated)
                ->except(['idPlan', 'nombreAdmin', 'emailAdmin'])
                ->all();

            $colegio = Colegio::create(['tenantId' => $tenantId] + $colegioData);

            // 3. Calcular fechas de suscripción según periodicidad del plan
            $plan    = Plan::findOrFail($validated['idPlan']);
            $inicio  = today();
            $vence   = $plan->periodicidad === 'anual'
                ? $inicio->copy()->addYear()
                : $inicio->copy()->addMonth();

            // 4. Crear suscripción activa
            Suscripcion::create([
                'idColegio'        => $colegio->idColegio,
                'idPlan'           => $plan->idPlan,
                'fechaInicio'      => $inicio,
                'fechaVencimiento' => $vence,
                'estado'           => 'activa',
            ]);

            // 5. Generar contraseña segura
            $password = Str::password(12);

            // 6. Crear usuario administrador del colegio
            User::create([
                'idColegio'           => $colegio->idColegio,
                'nombreUsuario'       => $validated['nombreAdmin'],
                'emailUsuario'        => $validated['emailAdmin'],
                'passwordUsuario'     => $password,
                'rolUsuario'          => 'ejecutivo',
                'estadoUsuario'       => 'activo',
                'must_change_password' => true,
            ]);

            // 7. Enviar credenciales por correo
            $urlAcceso = 'https://' . $tenantId . '.novareef.com';

            Mail::to($validated['emailAdmin'])->send(
                new CredencialesColegioMail(
                    $colegio->nombreColegio,
                    $urlAcceso,
                    $validated['emailAdmin'],
                    $password,
                )
            );
        });

        return redirect()
            ->route('colegios.index')
            ->with('success', 'Colegio registrado correctamente. Se enviaron las credenciales de acceso al correo del administrador.');
    }

    public function edit(int $id): View
    {
        $colegio = Colegio::findOrFail($id);

        return view('colegios.edit', compact('colegio'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $colegio   = Colegio::findOrFail($id);
        $validated = $request->validate($this->rules($id), $this->messages());

        $colegio->update($validated);

        return redirect()
            ->route('colegios.show', $id)
            ->with('success', 'Colegio actualizado correctamente.');
    }

    public function toggleEstado(int $id): RedirectResponse
    {
        $colegio = Colegio::findOrFail($id);

        $colegio->estadoColegio = ($colegio->estadoColegio === 'activo')
            ? 'suspendido'
            : 'activo';

        $colegio->save();

        $accion = $colegio->estadoColegio === 'activo' ? 'activado' : 'suspendido';

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
            'idPlan.required'    => 'Debes seleccionar un plan de suscripción.',
            'idPlan.exists'      => 'El plan seleccionado no existe.',
            'nombreAdmin.required' => 'El nombre del administrador es obligatorio.',
            'nombreAdmin.max'    => 'El nombre del administrador no puede superar 150 caracteres.',
            'emailAdmin.required' => 'El correo del administrador es obligatorio.',
            'emailAdmin.email'   => 'El correo del administrador no es válido.',
            'emailAdmin.unique'  => 'Este correo ya está registrado como usuario en la plataforma.',
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
            'fechaSuscripcion'    => ['nullable', 'date'],
            'fechaExpiracion'     => ['nullable', 'date'],
        ];
    }

    private function messages(): array
    {
        return [
            'nombreColegio.required'       => 'El nombre del colegio es obligatorio.',
            'nombreColegio.max'            => 'El nombre no puede superar 255 caracteres.',
            'codigoColegio.required'       => 'El código del colegio es obligatorio.',
            'codigoColegio.max'            => 'El código no puede superar 20 caracteres.',
            'codigoColegio.unique'         => 'Este código ya está en uso.',
            'emailColegio.required'        => 'El correo electrónico es obligatorio.',
            'emailColegio.email'           => 'Ingresa un correo electrónico válido.',
            'emailColegio.max'             => 'El correo no puede superar 255 caracteres.',
            'telefonoColegio.max'          => 'El teléfono no puede superar 20 caracteres.',
            'ciudadColegio.max'            => 'La ciudad no puede superar 100 caracteres.',
            'departamentoColegio.max'      => 'El departamento no puede superar 100 caracteres.',
            'paisColegio.required'         => 'El país es obligatorio.',
            'paisColegio.max'              => 'El país no puede superar 100 caracteres.',
            'logoColegio.url'              => 'El logo debe ser una URL válida.',
            'logoColegio.max'       => 'La URL del logo no puede superar 500 caracteres.',
            'fechaSuscripcion.date' => 'La fecha de suscripción no es válida.',
            'fechaExpiracion.date'         => 'La fecha de expiración no es válida.',
        ];
    }

    private function buildTenantId(string $codigo): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($codigo)));
    }
}

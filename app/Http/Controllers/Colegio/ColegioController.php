<?php

declare(strict_types=1);

namespace App\Http\Controllers\Colegio;

use App\Http\Controllers\Controller;
use App\Models\Colegio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        return view('colegios.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules(), $this->messages());

        $tenantId = $this->buildTenantId($validated['codigoColegio']);

        DB::table('tenants')->insertOrIgnore([
            'id'         => $tenantId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Colegio::create(['tenantId' => $tenantId] + $validated);

        return redirect()
            ->route('colegios.index')
            ->with('success', 'Colegio creado correctamente.');
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
            'planColegio'         => ['required', Rule::in(['basico', 'profesional', 'enterprise'])],
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
            'logoColegio.max'              => 'La URL del logo no puede superar 500 caracteres.',
            'planColegio.required'         => 'El plan es obligatorio.',
            'planColegio.in'               => 'El plan seleccionado no es válido.',
            'fechaSuscripcion.date'        => 'La fecha de suscripción no es válida.',
            'fechaExpiracion.date'         => 'La fecha de expiración no es válida.',
        ];
    }

    private function buildTenantId(string $codigo): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($codigo)));
    }
}

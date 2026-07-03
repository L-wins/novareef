<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Plan;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePlan extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        // El formulario envía 'modulos[]' pero la columna se llama 'modulosJSON'.
        // Renombramos antes de validar para que el campo validated() ya tenga el nombre correcto.
        $this->merge([
            'modulosJSON'       => $this->input('modulos', []),
            'incluyePaginaWeb'  => $this->boolean('incluyePaginaWeb'),
            'incluyeOnboarding' => $this->boolean('incluyeOnboarding'),
            'esVisible'         => $this->boolean('esVisible'),
            'esActivo'          => $this->boolean('esActivo'),
        ]);
    }

    public function rules(): array
    {
        return [
            'nombre'            => ['required', 'string', 'max:100'],
            'precio'            => ['required', 'numeric', 'min:0'],
            'periodicidad'      => ['required', 'in:' . implode(',', Plan::PERIODICIDADES)],
            'limiteArbitros'      => ['nullable', 'integer', 'min:1'],
            'limiteCuentasAdmin'  => ['nullable', 'integer', 'min:1'],
            'modulosJSON'       => ['nullable', 'array'],
            'modulosJSON.*'     => ['string'],
            'incluyePaginaWeb'  => ['boolean'],
            'incluyeOnboarding' => ['boolean'],
            'esVisible'         => ['boolean'],
            'esActivo'          => ['boolean'],
            'orden'             => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'       => 'El nombre del plan es obligatorio.',
            'nombre.max'            => 'El nombre no puede superar 100 caracteres.',
            'precio.required'       => 'El precio es obligatorio.',
            'precio.numeric'        => 'El precio debe ser un número.',
            'precio.min'            => 'El precio no puede ser negativo.',
            'periodicidad.required' => 'La periodicidad es obligatoria.',
            'periodicidad.in'       => 'Periodicidad inválida. Opciones: ' . implode(', ', Plan::PERIODICIDADES) . '.',
            'limiteArbitros.integer'=> 'El límite de árbitros debe ser un entero.',
            'limiteArbitros.min'    => 'El límite de árbitros debe ser al menos 1.',
            'limiteCuentasAdmin.integer' => 'El límite de cuentas admin debe ser un entero.',
            'limiteCuentasAdmin.min'     => 'El límite de cuentas admin debe ser al menos 1.',
            'orden.required'        => 'El orden es obligatorio.',
            'orden.integer'         => 'El orden debe ser un entero.',
            'orden.min'             => 'El orden no puede ser negativo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre'            => 'nombre del plan',
            'precio'            => 'precio',
            'periodicidad'      => 'periodicidad',
            'limiteArbitros'      => 'límite de árbitros',
            'limiteCuentasAdmin'  => 'límite de cuentas admin',
            'modulosJSON'       => 'módulos',
            'incluyePaginaWeb'  => 'página web incluida',
            'incluyeOnboarding' => 'onboarding incluido',
            'orden'             => 'orden',
        ];
    }
}

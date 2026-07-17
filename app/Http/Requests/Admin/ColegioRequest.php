<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Clase base con las reglas y mensajes compartidos entre crear y actualizar colegio.
 * Las subclases solo definen lo que cambia (unicidad del código, campos del admin).
 */
abstract class ColegioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La autorización se centraliza en el controlador / middleware.
    }

    /**
     * Reglas comunes a store y update. El parámetro $ignoreId permite
     * que UpdateColegio excluya el propio registro del unique check.
     */
    protected function reglasComunes(?int $ignoreId = null): array
    {
        $uniqueCodigo = \Illuminate\Validation\Rule::unique('colegios', 'codigoColegio');

        if ($ignoreId !== null) {
            $uniqueCodigo = $uniqueCodigo->ignore($ignoreId, 'idColegio');
        }

        return [
            'nombreColegio'       => ['required', 'string', 'max:255'],
            'codigoColegio'       => ['required', 'string', 'max:20', $uniqueCodigo],
            'emailColegio'        => ['required', 'email', 'max:255'],
            'telefonoColegio'     => ['nullable', 'string', 'max:20'],
            'direccionColegio'    => ['nullable', 'string'],
            'ciudadColegio'       => ['nullable', 'string', 'max:100'],
            'departamentoColegio' => ['nullable', 'string', 'max:100'],
            'paisColegio'         => ['required', 'string', 'max:100'],
            // 'url:http,https' restringe el esquema — la regla 'url' genérica
            // acepta cualquier esquema válido, incluido 'javascript:', lo que
            // abriría auto-XSS al renderizar <img src="..."> con ese valor.
            'logoColegio'         => ['nullable', 'url:http,https', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'required'       => 'El campo :attribute es obligatorio.',
            'email'          => 'Ingresa un correo electrónico válido.',
            'unique'         => 'El :attribute ingresado ya está en uso.',
            'url'            => 'El :attribute debe ser una URL válida.',
            'max.string'     => 'El campo :attribute no puede superar :max caracteres.',
            'integer'        => 'El campo :attribute debe ser un número entero.',
            'exists'         => 'El :attribute seleccionado no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombreColegio'       => 'nombre del colegio',
            'codigoColegio'       => 'código del colegio',
            'emailColegio'        => 'correo del colegio',
            'telefonoColegio'     => 'teléfono',
            'direccionColegio'    => 'dirección',
            'ciudadColegio'       => 'ciudad',
            'departamentoColegio' => 'departamento',
            'paisColegio'         => 'país',
            'logoColegio'         => 'logo',
            'idPlan'              => 'plan de suscripción',
            'nombreAdmin'         => 'nombre del administrador',
            'emailAdmin'          => 'correo del administrador',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Arbitro;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Reglas compartidas para actualizarMiPerfil y guardarPerfil (wizard primer acceso).
 * La diferencia entre ambas es solo qué campos adicionales expone cada vista,
 * no las reglas de validación base.
 */
class MiPerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        // Normaliza el checkbox a booleano antes de validar.
        $this->merge(['tieneVehiculo' => $this->boolean('tieneVehiculo')]);
    }

    public function rules(): array
    {
        return [
            'telefonoUsuario'   => ['nullable', 'string', 'max:20'],
            'lugarExpedicionCC' => ['nullable', 'string', 'max:100'],
            'pesoArbitro'       => ['nullable', 'numeric', 'min:30', 'max:200'],
            'estaturaArbitro'   => ['nullable', 'numeric', 'min:1.00', 'max:2.50'],
            'rhArbitro'         => ['nullable', 'string', 'max:5'],
            'epsArbitro'        => ['nullable', 'string', 'max:100'],
            'profesionArbitro'  => ['nullable', 'string', 'max:100'],
            'direccionArbitro'  => ['nullable', 'string', 'max:255'],
            'barrioArbitro'     => ['nullable', 'string', 'max:100'],
            'tieneVehiculo'     => ['boolean'],
            // required_if usa el valor booleano ya normalizado por prepareForValidation.
            'tipoVehiculo'      => ['nullable', 'required_if:tieneVehiculo,true', 'in:carro,moto,ambos'],
            'marcaVehiculo'     => ['nullable', 'required_if:tieneVehiculo,true', 'string', 'max:50'],
            'placaVehiculo'     => ['nullable', 'required_if:tieneVehiculo,true', 'string', 'max:20'],
            'colorVehiculo'     => ['nullable', 'required_if:tieneVehiculo,true', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'required_if' => 'El campo :attribute es obligatorio cuando tienes vehículo.',
            'in'          => 'El valor seleccionado para :attribute no es válido.',
            'max.string'  => 'El campo :attribute no puede superar :max caracteres.',
            'max.numeric' => 'El campo :attribute no puede ser mayor que :max.',
            'min.numeric' => 'El campo :attribute no puede ser menor que :min.',
        ];
    }
}

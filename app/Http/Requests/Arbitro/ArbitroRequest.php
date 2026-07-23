<?php

declare(strict_types=1);

namespace App\Http\Requests\Arbitro;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

abstract class ArbitroRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización de acceso (admin / propietario) se centraliza en el controlador.
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Normaliza el checkbox a booleano real para que required_if funcione.
        $this->merge([
            'tieneVehiculo' => $this->boolean('tieneVehiculo'),
        ]);
    }

    /**
     * Reglas compartidas por crear y actualizar (todo excepto email y password).
     */
    protected function reglasComunes(?int $idCategoriaActual = null): array
    {
        return [
            'nombreUsuario' => ['required', 'string', 'max:150'],
            'telefonoUsuario' => ['nullable', 'string', 'max:20'],

            'idCategoria' => ['required', 'integer', $this->reglaCategoriaAsignable($idCategoriaActual)],
            'numeroDocumento' => ['required', 'string', 'max:30'],
            'tipoDocumento' => ['required', 'in:cedula,pasaporte,extranjeria'],
            'lugarExpedicionCC' => ['nullable', 'string', 'max:100'],

            'pesoArbitro' => ['nullable', 'numeric', 'min:30', 'max:200'],
            'estaturaArbitro' => ['nullable', 'numeric', 'min:1.00', 'max:2.50'],
            'rhArbitro' => ['nullable', 'string', 'max:5'],
            'epsArbitro' => ['nullable', 'string', 'max:100'],

            'profesionArbitro' => ['nullable', 'string', 'max:100'],
            'fechaIngresoColegio' => ['nullable', 'date'],
            'direccionArbitro' => ['nullable', 'string', 'max:255'],
            'barrioArbitro' => ['nullable', 'string', 'max:100'],

            'tieneVehiculo' => ['boolean'],
            'tipoVehiculo' => ['nullable', 'required_if:tieneVehiculo,true', 'in:carro,moto,ambos'],
            'marcaVehiculo' => ['nullable', 'required_if:tieneVehiculo,true', 'string', 'max:50'],
            'placaVehiculo' => ['nullable', 'required_if:tieneVehiculo,true', 'string', 'max:20'],
            'colorVehiculo' => ['nullable', 'required_if:tieneVehiculo,true', 'string', 'max:30'],
        ];
    }

    protected function reglaCategoriaAsignable(?int $idCategoriaActual = null): Exists
    {
        $idColegio = (int) Auth::user()->idColegio;

        return Rule::exists('categorias_arbitro', 'idCategoria')
            ->where(function ($query) use ($idColegio, $idCategoriaActual): void {
                $query->where('idColegio', $idColegio)
                    ->where(function ($scope) use ($idCategoriaActual): void {
                        $scope->where('activa', true);

                        if ($idCategoriaActual !== null) {
                            $scope->orWhere('idCategoria', $idCategoriaActual);
                        }
                    });
            });
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'required_if' => 'El campo :attribute es obligatorio cuando el árbitro tiene vehículo.',
            'email' => 'Ingresa un correo electrónico válido.',
            'unique' => 'El :attribute ingresado ya está registrado.',
            'confirmed' => 'La confirmación de la contraseña no coincide.',
            'in' => 'El valor seleccionado para :attribute no es válido.',
            'exists' => 'La :attribute seleccionada no es válida.',
            'numeric' => 'El campo :attribute debe ser un valor numérico.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'date' => 'El campo :attribute no es una fecha válida.',
            'boolean' => 'El campo :attribute no es válido.',
            'max.string' => 'El campo :attribute no puede superar :max caracteres.',
            'max.numeric' => 'El campo :attribute no puede ser mayor que :max.',
            'min.string' => 'El campo :attribute debe tener al menos :min caracteres.',
            'min.numeric' => 'El campo :attribute no puede ser menor que :min.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombreUsuario' => 'nombre',
            'emailUsuario' => 'correo electrónico',
            'passwordUsuario' => 'contraseña',
            'telefonoUsuario' => 'teléfono',
            'idCategoria' => 'categoría',
            'numeroDocumento' => 'número de documento',
            'tipoDocumento' => 'tipo de documento',
            'lugarExpedicionCC' => 'lugar de expedición',
            'pesoArbitro' => 'peso',
            'estaturaArbitro' => 'estatura',
            'rhArbitro' => 'RH',
            'epsArbitro' => 'EPS',
            'profesionArbitro' => 'profesión',
            'fechaIngresoColegio' => 'fecha de ingreso',
            'direccionArbitro' => 'dirección',
            'barrioArbitro' => 'barrio',
            'tieneVehiculo' => 'vehículo',
            'tipoVehiculo' => 'tipo de vehículo',
            'marcaVehiculo' => 'marca del vehículo',
            'placaVehiculo' => 'placa del vehículo',
            'colorVehiculo' => 'color del vehículo',
        ];
    }
}

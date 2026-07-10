<?php

declare(strict_types=1);

namespace App\Http\Requests\Academico;

use App\Models\SesionAcademica;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreSesionAcademicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $idColegio = (int) Auth::user()->idColegio;

        return [
            'idTipoSesion' => [
                'required', 'integer',
                Rule::exists('tipos_sesion_academica', 'idTipoSesion')->where('idColegio', $idColegio),
            ],
            'modalidad'   => ['required', Rule::in([SesionAcademica::MODALIDAD_PRESENCIAL, SesionAcademica::MODALIDAD_VIRTUAL])],
            'urlVirtual'  => ['nullable', 'url', 'max:255', 'required_if:modalidad,' . SesionAcademica::MODALIDAD_VIRTUAL],
            'tema'        => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'lugar'       => ['nullable', 'string', 'max:150'],
            'fechaSesion' => ['required', 'date'],
            'horaSesion'  => ['required', 'date_format:H:i'],
            'duracionMinutos' => ['required', 'integer', 'min:1'],
            'dirigidaA'   => ['required', Rule::in([SesionAcademica::DIRIGIDA_TODOS, SesionAcademica::DIRIGIDA_CATEGORIA])],
            'idCategoria' => [
                'nullable', 'integer', 'required_if:dirigidaA,' . SesionAcademica::DIRIGIDA_CATEGORIA,
                Rule::exists('categorias_arbitro', 'idCategoria')->where('idColegio', $idColegio),
            ],
            'modoAsistencia' => ['required', Rule::in([SesionAcademica::MODO_MANUAL, SesionAcademica::MODO_SCANNER])],
        ];
    }

    public function messages(): array
    {
        return [
            'idTipoSesion.required'    => 'Selecciona el tipo de sesión.',
            'urlVirtual.required_if'   => 'La URL es obligatoria para una sesión virtual.',
            'tema.required'            => 'El tema es obligatorio.',
            'fechaSesion.required'     => 'La fecha es obligatoria.',
            'horaSesion.required'      => 'La hora es obligatoria.',
            'duracionMinutos.required' => 'La duración es obligatoria.',
            'idCategoria.required_if'  => 'Selecciona la categoría a la que va dirigida.',
        ];
    }
}

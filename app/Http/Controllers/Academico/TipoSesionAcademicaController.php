<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academico;

use App\Http\Controllers\Concerns\CatalogoActivableController;
use App\Http\Requests\Academico\StoreTipoSesionRequest;
use App\Models\TipoSesionAcademica;
use App\Services\TipoSesionAcademicaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

class TipoSesionAcademicaController extends CatalogoActivableController
{
    public function __construct(
        private readonly TipoSesionAcademicaService $tipos,
    ) {}

    public function store(StoreTipoSesionRequest $request): RedirectResponse
    {
        $this->tipos->crear($this->idColegioActivo(), $request->validated());

        return redirect()
            ->route('tipos-sesion-academica.index')
            ->with('success', 'Tipo de sesión creado correctamente.');
    }

    protected function modeloClass(): string
    {
        return TipoSesionAcademica::class;
    }

    protected function rutaBase(): string
    {
        return 'tipos-sesion-academica';
    }

    protected function vista(): string
    {
        return 'academico.tipos';
    }

    protected function etiquetaEntidad(): string
    {
        return 'Tipo de sesión';
    }

    protected function columnasOrden(): array
    {
        return ['orden', 'etiqueta'];
    }

    protected function alternarActivo(Model $tipo): string
    {
        return $this->tipos->alternarActivo($tipo);
    }

    protected function eliminarRegistro(Model $tipo): void
    {
        $this->tipos->eliminar($tipo);
    }
}

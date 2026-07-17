<?php

declare(strict_types=1);

namespace App\Http\Controllers\Sancion;

use App\Http\Controllers\Concerns\CatalogoActivableController;
use App\Http\Requests\Sancion\StoreTipoSancionRequest;
use App\Models\TipoSancion;
use App\Services\TipoSancionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

class TipoSancionController extends CatalogoActivableController
{
    public function __construct(
        private readonly TipoSancionService $tipos,
    ) {}

    public function store(StoreTipoSancionRequest $request): RedirectResponse
    {
        $this->tipos->crear($this->idColegioActivo(), $request->validated());

        return redirect()
            ->route('tipos-sancion.index')
            ->with('success', 'Tipo de sanción creado correctamente.');
    }

    protected function modeloClass(): string
    {
        return TipoSancion::class;
    }

    protected function rutaBase(): string
    {
        return 'tipos-sancion';
    }

    protected function vista(): string
    {
        return 'sanciones.tipos';
    }

    protected function etiquetaEntidad(): string
    {
        return 'Tipo de sanción';
    }

    protected function columnasOrden(): array
    {
        return ['orden', 'nombre'];
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

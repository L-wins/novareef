<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Base compartida por los controladores de catálogo "por colegio" con el
 * mismo shape (TipoSancion, TipoSesionAcademica): listar, activar/desactivar,
 * eliminar (bloqueado si ya tiene registros dependientes). store() queda en
 * cada subclase porque necesita su propio FormRequest tipado — Laravel
 * resuelve el FormRequest correcto por el type-hint del método, así que no
 * se puede abstraer sin perder esa inyección automática.
 */
abstract class CatalogoActivableController extends Controller
{
    use ResuelveColegio;

    /** @return class-string<Model> */
    abstract protected function modeloClass(): string;

    /** Prefijo de la ruta nombrada, ej. 'tipos-sancion'. */
    abstract protected function rutaBase(): string;

    /** Vista del listado, ej. 'sanciones.tipos'. */
    abstract protected function vista(): string;

    /** Nombre legible de la entidad para los mensajes flash, ej. 'Tipo de sanción'. */
    abstract protected function etiquetaEntidad(): string;

    /** Columnas de orden del listado, en orden de prioridad. */
    abstract protected function columnasOrden(): array;

    abstract protected function alternarActivo(Model $tipo): string;

    /** @throws \RuntimeException  Si el registro no se puede eliminar. */
    abstract protected function eliminarRegistro(Model $tipo): void;

    public function index(): View
    {
        $modelo = $this->modeloClass();
        $query  = $modelo::where('idColegio', $this->idColegioActivo());

        foreach ($this->columnasOrden() as $columna) {
            $query->orderBy($columna);
        }

        return view($this->vista(), ['tipos' => $query->get()]);
    }

    public function cambiarEstado(int $id): RedirectResponse
    {
        $tipo   = $this->registroDelColegio($id);
        $estado = $this->alternarActivo($tipo);

        return redirect()
            ->route("{$this->rutaBase()}.index")
            ->with('success', "{$this->etiquetaEntidad()} {$estado} correctamente.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $tipo = $this->registroDelColegio($id);

        try {
            $this->eliminarRegistro($tipo);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route("{$this->rutaBase()}.index")
            ->with('success', "{$this->etiquetaEntidad()} eliminado correctamente.");
    }

    protected function registroDelColegio(int $id): Model
    {
        $modelo = $this->modeloClass();
        $tipo   = $modelo::findOrFail($id);

        abort_unless((int) $tipo->idColegio === $this->idColegioActivo(), 403);

        return $tipo;
    }
}

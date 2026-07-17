<?php

declare(strict_types=1);

namespace App\Http\Controllers\Arbitro;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Arbitro\SubirFotoArbitroRequest;
use App\Models\Arbitro;
use App\Services\ArbitroFotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ArbitroFotoController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly ArbitroFotoService $fotos,
    ) {}

    public function subir(SubirFotoArbitroRequest $request, int $id): RedirectResponse
    {
        $arbitro = $this->arbitroAutorizado($id);

        $this->fotos->actualizar($arbitro, $request->file('foto'));

        return back()->with('success', 'Foto de perfil actualizada correctamente.');
    }

    public function eliminar(int $id): RedirectResponse
    {
        $arbitro = $this->arbitroAutorizado($id);

        $this->fotos->eliminar($arbitro);

        return back()->with('success', 'Foto de perfil eliminada.');
    }

    // ── Helpers privados ──────────────────

    /**
     * Resuelve el árbitro por ID verificando que el usuario autenticado pueda
     * gestionar su foto: es el propio árbitro, o es staff del mismo colegio
     * con permiso editar-arbitros. Centraliza lo que antes se repetía en
     * subir() y eliminar() (findOrFail + autorizar por separado).
     */
    private function arbitroAutorizado(int $id): Arbitro
    {
        $arbitro = Arbitro::findOrFail($id);

        $esPropietario = (int) $arbitro->idUsuario === (int) Auth::id();
        $puedeEditar   = (int) $arbitro->idColegio === $this->idColegioActivo()
            && Auth::user()->can('editar-arbitros');

        abort_unless($esPropietario || $puedeEditar, 403);

        return $arbitro;
    }
}

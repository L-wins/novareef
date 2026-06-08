<?php

declare(strict_types=1);

namespace App\Http\Controllers\Arbitro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Arbitro\SubirFotoArbitroRequest;
use App\Models\Arbitro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ArbitroFotoController extends Controller
{
    private const DISCO = 'public';
    private const DIRECTORIO = 'fotos-arbitros';

    public function subir(SubirFotoArbitroRequest $request, int $id): RedirectResponse
    {
        $arbitro = Arbitro::findOrFail($id);

        $this->autorizar($arbitro);

        $this->eliminarFotoActual($arbitro);

        $ruta = $request->file('foto')->store(self::DIRECTORIO, self::DISCO);
        $arbitro->update(['fotoPerfil' => $ruta]);

        return back()->with('success', 'Foto de perfil actualizada correctamente.');
    }

    public function eliminar(int $id): RedirectResponse
    {
        $arbitro = Arbitro::findOrFail($id);

        $this->autorizar($arbitro);

        $this->eliminarFotoActual($arbitro);
        $arbitro->update(['fotoPerfil' => null]);

        return back()->with('success', 'Foto de perfil eliminada.');
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

    /**
     * Permite subir/eliminar foto si el usuario es el propietario
     * o si pertenece al mismo colegio y tiene permiso editar-arbitros.
     */
    private function autorizar(Arbitro $arbitro): void
    {
        $esPropietario = (int) $arbitro->idUsuario === (int) Auth::id();
        $puedeEditar   = (int) $arbitro->idColegio === (int) Auth::user()->idColegio
            && Auth::user()->can('editar-arbitros');

        abort_unless($esPropietario || $puedeEditar, 403);
    }

    private function eliminarFotoActual(Arbitro $arbitro): void
    {
        if ($arbitro->fotoPerfil && Storage::disk(self::DISCO)->exists($arbitro->fotoPerfil)) {
            Storage::disk(self::DISCO)->delete($arbitro->fotoPerfil);
        }
    }
}

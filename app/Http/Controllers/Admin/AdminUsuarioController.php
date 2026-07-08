<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Colegio;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUsuarioController extends Controller
{
    private const ROLES = ['arbitro', 'ejecutivo', 'tesorero', 'designador', 'sanciones', 'tecnico', 'superadmin'];

    public function index(Request $request): View
    {
        $query = User::with('colegio')->orderByDesc('idUsuario');

        if ($search = trim((string) $request->input('q', ''))) {
            $query->where(function ($q) use ($search): void {
                $q->where('nombreUsuario', 'like', "%{$search}%")
                  ->orWhere('emailUsuario', 'like', "%{$search}%");
            });
        }

        if ($request->filled('colegio')) {
            $query->where('idColegio', $request->integer('colegio'));
        }

        if ($request->filled('rol')) {
            $query->where('rolUsuario', $request->string('rol'));
        }

        $usuarios = $query->paginate(20)->withQueryString();
        $colegios = Colegio::orderBy('nombreColegio')->get(['idColegio', 'nombreColegio']);

        return view('admin.usuarios.index', [
            'usuarios' => $usuarios,
            'colegios' => $colegios,
            'roles'    => self::ROLES,
        ]);
    }

    /**
     * Activa o suspende una cuenta de colegio — herramienta de soporte para
     * desbloquear/bloquear un usuario sin tocar la base de datos a mano.
     */
    public function toggleEstado(int $id): RedirectResponse
    {
        $usuario = User::findOrFail($id);

        $usuario->estadoUsuario = $usuario->estadoUsuario === 'activo' ? 'suspendido' : 'activo';
        $usuario->save();

        return back()->with('success', "Cuenta de {$usuario->nombreUsuario} actualizada a \"{$usuario->estadoUsuario}\".");
    }
}

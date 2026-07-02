<?php

declare(strict_types=1);

namespace App\Http\Controllers\Arbitro;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Arbitro\MiPerfilRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ArbitroPerfilController extends Controller
{
    use ResuelveColegio;

    /** Campos de MiPerfilRequest que pertenecen al modelo User, no al Arbitro. */
    private const CAMPOS_USUARIO = ['telefonoUsuario'];

    public function show(): View
    {
        return view('arbitros.mi-perfil', [
            'arbitro' => $this->arbitroAutenticado(['usuario', 'categoria', 'colegio', 'estado', 'documentos']),
        ]);
    }

    public function update(MiPerfilRequest $request): RedirectResponse
    {
        $arbitro = $this->arbitroAutenticado(['usuario']);
        $datos   = $request->validated();

        DB::transaction(function () use ($arbitro, $datos): void {
            $arbitro->usuario->update(collect($datos)->only(self::CAMPOS_USUARIO)->toArray());
            $arbitro->update(collect($datos)->except(self::CAMPOS_USUARIO)->toArray());
        });

        return redirect()->route('arbitros.mi-perfil')->with('success', 'Perfil actualizado correctamente.');
    }

    public function completar(): View
    {
        return view('arbitros.completar-perfil', [
            'arbitro' => $this->arbitroAutenticado(['usuario', 'categoria']),
        ]);
    }

    public function guardar(MiPerfilRequest $request): RedirectResponse
    {
        $arbitro = $this->arbitroAutenticado(['usuario']);
        $datos   = $request->validated();

        DB::transaction(function () use ($arbitro, $datos): void {
            $arbitro->usuario->update(collect($datos)->only(self::CAMPOS_USUARIO)->toArray());
            $arbitro->update(collect($datos)->except(self::CAMPOS_USUARIO)->toArray());
        });

        return redirect()->route('dashboard')->with('success', 'Perfil completado correctamente. ¡Bienvenido a NovaReef!');
    }
}

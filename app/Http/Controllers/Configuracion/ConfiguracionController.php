<?php

declare(strict_types=1);

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ActualizarConfiguracionRequest;
use App\Models\ConfiguracionColegio;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ConfiguracionController extends Controller
{
    use ResuelveColegio;

    // La autorización (solo ejecutivo) la cubre el middleware permission:editar-arbitros
    // definido en routes/web.php. No se duplica aquí.

    public function index(): View
    {
        $idColegio = $this->idColegioActivo();

        return view('configuracion.index', [
            'diaDisponibilidad' => ConfiguracionColegio::getDiaDisponibilidad($idColegio),
            'diasSemana'        => ConfiguracionColegio::diasSemana(),
        ]);
    }

    public function update(ActualizarConfiguracionRequest $request): RedirectResponse
    {
        ConfiguracionColegio::set(
            $this->idColegioActivo(),
            ConfiguracionColegio::DIA_DISPONIBILIDAD,
            $request->validated('dia_disponibilidad'),
        );

        return back()->with('success', 'Configuración guardada correctamente.');
    }
}

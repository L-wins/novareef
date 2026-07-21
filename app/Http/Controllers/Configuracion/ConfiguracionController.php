<?php

declare(strict_types=1);

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ActualizarConfiguracionRequest;
use App\Http\Requests\Configuracion\SubirLogoColegioRequest;
use App\Models\ConfiguracionColegio;
use App\Services\ColegioLogoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ConfiguracionController extends Controller
{
    use ResuelveColegio;

    // La autorización (solo ejecutivo) la cubre el middleware permission:editar-arbitros
    // definido en routes/web.php. No se duplica aquí.

    public function __construct(private readonly ColegioLogoService $logos) {}

    /**
     * "General" — identidad/perfil del colegio (logo + datos de cuenta). Los
     * datos de cuenta (nombre, país, email...) son de solo lectura acá: los
     * gestiona el superadmin desde su panel, esta pantalla es la vista del
     * propio colegio sobre su perfil, no el CRUD.
     */
    public function general(): View
    {
        return view('configuracion.general', [
            'colegio' => Auth::user()->colegio,
        ]);
    }

    /**
     * "Colegio" — reglas de operación propias de este colegio (no
     * compartidas con la plataforma en general): disponibilidad, plazos de
     * confirmación, cobro automático de mensualidad. Todo lo que varía de un
     * colegio a otro vive acá, separado del perfil/identidad de "General".
     */
    public function colegio(): View
    {
        $idColegio = $this->idColegioActivo();

        return view('configuracion.colegio', [
            'diaDisponibilidad'         => ConfiguracionColegio::getDiaDisponibilidad($idColegio),
            'diasSemana'                => ConfiguracionColegio::diasSemana(),
            'horasLimiteConfirmacion'   => ConfiguracionColegio::getHorasLimiteConfirmacion($idColegio),
            'montoMensualidad'          => ConfiguracionColegio::getMontoMensualidad($idColegio),
            'diaVencimientoMensualidad' => ConfiguracionColegio::getDiaVencimientoMensualidad($idColegio),
        ]);
    }

    public function actualizarLogo(SubirLogoColegioRequest $request): RedirectResponse
    {
        $colegio = Auth::user()->colegio;

        abort_unless($colegio !== null, 404);

        $this->logos->actualizar($colegio, $request->file('logo'));

        return back()->with('success', 'Logo del colegio actualizado correctamente.');
    }

    public function eliminarLogo(): RedirectResponse
    {
        $colegio = Auth::user()->colegio;

        abort_unless($colegio !== null, 404);

        $this->logos->eliminar($colegio);

        return back()->with('success', 'Logo del colegio eliminado.');
    }

    public function update(ActualizarConfiguracionRequest $request): RedirectResponse
    {
        $idColegio = $this->idColegioActivo();
        $datos     = $request->validated();

        // Atómico: 4 escrituras independientes (una tabla EAV, no una fila
        // sola) — sin transacción, un fallo a mitad de camino (ej. conexión
        // caída tras la 2ª escritura) deja la configuración del colegio en un
        // estado mixto, mitad con los valores nuevos y mitad con los viejos.
        DB::transaction(function () use ($idColegio, $datos): void {
            ConfiguracionColegio::set($idColegio, ConfiguracionColegio::DIA_DISPONIBILIDAD, $datos['dia_disponibilidad']);
            ConfiguracionColegio::set($idColegio, ConfiguracionColegio::HORAS_LIMITE_CONFIRMACION, $datos['horas_limite_confirmacion']);
            ConfiguracionColegio::set($idColegio, ConfiguracionColegio::MONTO_MENSUALIDAD, $datos['monto_mensualidad'] ?? 0);
            ConfiguracionColegio::set($idColegio, ConfiguracionColegio::DIA_VENCIMIENTO_MENSUALIDAD, $datos['dia_vencimiento_mensualidad'] ?? 5);
        });

        return back()->with('success', 'Configuración guardada correctamente.');
    }
}

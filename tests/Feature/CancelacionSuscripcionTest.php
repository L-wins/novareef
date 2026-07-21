<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\VencerSuscripcionesJob;
use App\Models\Colegio;
use App\Models\Suscripcion;
use App\Services\SuscripcionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Motivado por un incidente real: cancelar la suscripción de un colegio
 * cortaba el acceso de inmediato (estado='suspendida'), aunque el colegio
 * ya tuviera el período pagado hasta fechaVencimiento — distinto al modelo
 * de cancelación de Netflix/Spotify, donde se conserva el acceso hasta el
 * fin del período ya pagado. Ahora cancelar() solo marca la intención
 * (fechaCancelacion) y VencerSuscripcionesJob es quien, al llegar la fecha,
 * pasa el estado a 'vencida'.
 */
class CancelacionSuscripcionTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    public function test_cancelar_no_corta_el_acceso_de_inmediato(): void
    {
        $colegio = $this->crearColegio();

        $suscripcion = app(SuscripcionService::class)->cancelar($colegio);

        $this->assertSame('activa', $suscripcion->estado);
        $this->assertNotNull($suscripcion->fechaCancelacion);
        $this->assertNotNull($colegio->fresh()->suscripcionActiva);
    }

    public function test_no_se_puede_cancelar_dos_veces(): void
    {
        $colegio = $this->crearColegio();
        $servicio = app(SuscripcionService::class);

        $servicio->cancelar($colegio);

        $this->expectException(\RuntimeException::class);
        $servicio->cancelar($colegio);
    }

    public function test_el_job_vence_una_suscripcion_cancelada_cuyo_plazo_ya_paso(): void
    {
        $colegio = $this->crearColegio();

        Suscripcion::where('idColegio', $colegio->idColegio)->update([
            'fechaVencimiento' => today()->subDay(),
            'fechaCancelacion' => now()->subDays(5),
        ]);

        (new VencerSuscripcionesJob())->handle();

        $this->assertSame('vencida', Suscripcion::where('idColegio', $colegio->idColegio)->value('estado'));
        $this->assertNull($colegio->fresh()->suscripcionActiva);
    }

    public function test_el_job_no_toca_suscripciones_vigentes_sin_vencer(): void
    {
        $colegio = $this->crearColegio();

        app(SuscripcionService::class)->cancelar($colegio);

        (new VencerSuscripcionesJob())->handle();

        $this->assertSame('activa', Suscripcion::where('idColegio', $colegio->idColegio)->value('estado'));
        $this->assertNotNull($colegio->fresh()->suscripcionActiva);
    }

    public function test_el_job_vence_suscripciones_sin_cancelacion_explicita_que_simplemente_nadie_renovo(): void
    {
        $colegio = $this->crearColegio();

        Suscripcion::where('idColegio', $colegio->idColegio)->update([
            'fechaVencimiento' => today()->subDays(3),
        ]);

        (new VencerSuscripcionesJob())->handle();

        $this->assertSame('vencida', Suscripcion::where('idColegio', $colegio->idColegio)->value('estado'));
    }

    public function test_admin_puede_cancelar_desde_el_panel_y_el_colegio_conserva_acceso(): void
    {
        $admin   = \App\Models\Admin::create([
            'nombre' => 'Super', 'email' => 'super@test.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'), 'activo' => true,
        ]);
        $colegio = $this->crearColegio();

        $this->actingAs($admin, 'admin')
            ->put("/novareef-panel/suscripciones/colegio/{$colegio->idColegio}/cancelar")
            ->assertRedirect();

        $suscripcion = Suscripcion::where('idColegio', $colegio->idColegio)->first();
        $this->assertSame('activa', $suscripcion->estado);
        $this->assertNotNull($suscripcion->fechaCancelacion);
    }
}

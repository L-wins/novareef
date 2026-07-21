<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Models\Colegio;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\DuskTestCase;

/**
 * Smoke test en navegador real de la sección Colegio de Configuración — el
 * botón "Editar" vive en el page-header, fuera del <form>, asociado vía
 * form="cfg-colegio-form" (ver configuracion.js). Eso es exactamente el tipo
 * de suposición de DOM que PHPUnit/HTTP nunca puede probar: solo un
 * navegador real ejecuta el JS y confirma que initEditMode() encuentra los
 * botones y que el submit realmente guarda.
 */
class ConfiguracionColegioEdicionTest extends DuskTestCase
{
    use DatabaseTruncation;
    use CreaColegioDePrueba;

    private function crearEjecutivo(Colegio $colegio): User
    {
        foreach (['editar-arbitros'] as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        $rol = Role::firstOrCreate(['name' => 'ejecutivo', 'guard_name' => 'web']);
        $rol->syncPermissions(['editar-arbitros']);

        $usuario = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'ejecutivo']);
        $usuario->assignRole('ejecutivo');

        // loginAs() abre una sesión real que sí pasa por ExigirAceptacionPolitica.
        app(\App\Services\PoliticaPrivacidadService::class)->registrarAceptacionGeneral($usuario, '127.0.0.1');

        return $usuario;
    }

    public function test_los_campos_arrancan_bloqueados_y_editar_los_habilita(): void
    {
        $colegio   = $this->crearColegio($this->crearPlan());
        $ejecutivo = $this->crearEjecutivo($colegio);

        $this->browse(function (Browser $browser) use ($ejecutivo) {
            $browser->loginAs($ejecutivo, 'web')
                ->visit('/configuracion/colegio')
                ->waitFor('#horas_limite_confirmacion')
                ->assertDisabled('#horas_limite_confirmacion')
                ->assertVisible('[data-edit-btn]')
                ->assertMissing('[data-edit-save]:not([hidden])')
                ->click('[data-edit-btn]')
                ->pause(200)
                ->assertEnabled('#horas_limite_confirmacion')
                ->assertVisible('[data-edit-save]');
        });
    }

    public function test_editar_y_guardar_persiste_los_valores_reales(): void
    {
        $colegio   = $this->crearColegio($this->crearPlan());
        $ejecutivo = $this->crearEjecutivo($colegio);

        $this->browse(function (Browser $browser) use ($ejecutivo) {
            $browser->loginAs($ejecutivo, 'web')
                ->visit('/configuracion/colegio')
                ->waitFor('[data-edit-btn]')
                ->click('[data-edit-btn]')
                ->pause(200)
                ->clear('#horas_limite_confirmacion')
                ->type('#horas_limite_confirmacion', '9')
                ->clear('#monto_mensualidad')
                ->type('#monto_mensualidad', '35000')
                ->waitForReload(fn (Browser $b) => $b->click('[data-edit-save]'))
                ->assertPathIs('/novareef/public/configuracion/colegio');
        });

        // El botón vive fuera del <form> (form="cfg-colegio-form") — si esa
        // asociación fallara en un navegador real, el click no dispararía
        // el submit y estos valores seguirían siendo los de fábrica.
        $this->assertSame(9, \App\Models\ConfiguracionColegio::getHorasLimiteConfirmacion($colegio->idColegio));
        $this->assertSame(35000.0, \App\Models\ConfiguracionColegio::getMontoMensualidad($colegio->idColegio));
    }
}

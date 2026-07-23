<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Cobertura de ArbitroPerfilController — perfil propio y wizard de primer
 * acceso ("completar perfil"). Sin tests de Feature previos.
 */
class ArbitroPerfilControllerTest extends TestCase
{
    use CreaColegioDePrueba;
    use RefreshDatabase;

    public function test_el_arbitro_ve_su_propio_perfil(): void
    {
        $colegio = $this->crearColegio();
        $arbitro = $this->crearArbitro($colegio);

        $this->actingAs($arbitro->usuario)->get(route('arbitros.mi-perfil'))
            ->assertOk()
            ->assertViewHas('arbitro', fn ($a) => $a->idArbitro === $arbitro->idArbitro)
            ->assertSee('rhArbitro')
            ->assertSee('profesionArbitro')
            ->assertSee('lugarExpedicionCC');
    }

    public function test_el_arbitro_actualiza_su_perfil(): void
    {
        $colegio = $this->crearColegio();
        $arbitro = $this->crearArbitro($colegio);

        $this->actingAs($arbitro->usuario)->put(route('arbitros.mi-perfil.update'), [
            'telefonoUsuario' => '3007654321',
            'pesoArbitro' => 75,
            'estaturaArbitro' => 1.78,
        ])->assertRedirect(route('arbitros.mi-perfil'));

        $this->assertSame('3007654321', $arbitro->usuario->fresh()->telefonoUsuario);
        $this->assertEquals(75, $arbitro->fresh()->pesoArbitro);
    }

    public function test_el_arbitro_actualiza_los_campos_de_completitud_desde_mi_perfil(): void
    {
        $colegio = $this->crearColegio();
        $arbitro = $this->crearArbitro($colegio);

        $this->actingAs($arbitro->usuario)->put(route('arbitros.mi-perfil.update'), [
            'telefonoUsuario' => '3007654321',
            'pesoArbitro' => 75,
            'estaturaArbitro' => 1.78,
            'rhArbitro' => 'O+',
            'epsArbitro' => 'Sura',
            'consentimientoDatosSensibles' => '1',
            'profesionArbitro' => 'Entrenador',
            'direccionArbitro' => 'Calle 1 # 2-3',
            'barrioArbitro' => 'Centro',
            'lugarExpedicionCC' => 'Bogotá',
        ])->assertRedirect(route('arbitros.mi-perfil'));

        $arbitro->refresh();

        $this->assertSame('O+', $arbitro->rhArbitro);
        $this->assertSame('Sura', $arbitro->epsArbitro);
        $this->assertSame('Entrenador', $arbitro->profesionArbitro);
        $this->assertSame('Bogotá', $arbitro->lugarExpedicionCC);
    }

    public function test_vehiculo_exige_los_campos_relacionados(): void
    {
        $colegio = $this->crearColegio();
        $arbitro = $this->crearArbitro($colegio);

        $this->actingAs($arbitro->usuario)->put(route('arbitros.mi-perfil.update'), [
            'tieneVehiculo' => '1',
        ])->assertSessionHasErrors(['tipoVehiculo', 'marcaVehiculo', 'placaVehiculo', 'colorVehiculo']);
    }

    public function test_completar_perfil_wizard_redirige_al_dashboard(): void
    {
        $colegio = $this->crearColegio();
        $arbitro = $this->crearArbitro($colegio);

        $this->actingAs($arbitro->usuario)->get(route('arbitros.completar-perfil'))->assertOk();

        $this->actingAs($arbitro->usuario)->post(route('arbitros.guardar-perfil'), [
            'telefonoUsuario' => '3001112233',
        ])->assertRedirect(route('dashboard'));

        $this->assertSame('3001112233', $arbitro->usuario->fresh()->telefonoUsuario);
    }

    public function test_un_arbitro_no_puede_ver_el_perfil_de_otro(): void
    {
        $colegio = $this->crearColegio();
        $arbitroA = $this->crearArbitro($colegio);
        $arbitroB = $this->crearArbitro($colegio);

        // arbitroAutenticado() resuelve siempre por el usuario logueado —
        // no hay parámetro {id} en la ruta para intentar cruzar, así que la
        // única forma de "ver el perfil de otro" sería que el propio
        // arbitroAutenticado() resolviera mal. Confirma que cada uno ve el suyo.
        $this->actingAs($arbitroB->usuario)->get(route('arbitros.mi-perfil'))
            ->assertViewHas('arbitro', fn ($a) => $a->idArbitro === $arbitroB->idArbitro && $a->idArbitro !== $arbitroA->idArbitro);
    }
}

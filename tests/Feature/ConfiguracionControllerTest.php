<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Colegio;
use App\Models\ConfiguracionColegio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Cobertura HTTP de ConfiguracionController — antes solo existía
 * ConfiguracionColegioTest, que prueba el modelo directo y nunca ejerce la
 * ruta real (permiso, FormRequest, redirect, persistencia end-to-end).
 */
class ConfiguracionControllerTest extends TestCase
{
    use RefreshDatabase;
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

        return $usuario;
    }

    public function test_general_muestra_la_identidad_del_colegio(): void
    {
        $colegio   = $this->crearColegio($this->crearPlan());
        $ejecutivo = $this->crearEjecutivo($colegio);

        $response = $this->actingAs($ejecutivo)->get('/configuracion');

        $response->assertOk();
        $response->assertSee($colegio->nombreColegio);
        $response->assertSee($colegio->codigoColegio);
        $response->assertViewIs('configuracion.general');
    }

    public function test_colegio_muestra_los_valores_actuales(): void
    {
        $colegio   = $this->crearColegio($this->crearPlan());
        $ejecutivo = $this->crearEjecutivo($colegio);

        ConfiguracionColegio::set($colegio->idColegio, ConfiguracionColegio::DIA_DISPONIBILIDAD, '3');
        ConfiguracionColegio::set($colegio->idColegio, ConfiguracionColegio::MONTO_MENSUALIDAD, '30000');

        $response = $this->actingAs($ejecutivo)->get('/configuracion/colegio');

        $response->assertOk();
        $response->assertSee('Miércoles');
        $response->assertViewHas('montoMensualidad', 30000.0);
        $response->assertViewIs('configuracion.colegio');
    }

    public function test_actualizar_guarda_las_cuatro_configuraciones(): void
    {
        $colegio   = $this->crearColegio($this->crearPlan());
        $ejecutivo = $this->crearEjecutivo($colegio);

        $response = $this->actingAs($ejecutivo)->put('/configuracion', [
            'dia_disponibilidad'          => 2,
            'horas_limite_confirmacion'   => 12,
            'monto_mensualidad'           => 25000,
            'dia_vencimiento_mensualidad' => 10,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame(2, ConfiguracionColegio::getDiaDisponibilidad($colegio->idColegio));
        $this->assertSame(12, ConfiguracionColegio::getHorasLimiteConfirmacion($colegio->idColegio));
        $this->assertSame(25000.0, ConfiguracionColegio::getMontoMensualidad($colegio->idColegio));
        $this->assertSame(10, ConfiguracionColegio::getDiaVencimientoMensualidad($colegio->idColegio));
    }

    public function test_actualizar_sin_monto_de_mensualidad_lo_deja_en_cero(): void
    {
        // monto_mensualidad es 'nullable' — un colegio que no usa cobro
        // automático puede omitir el campo (el form envía vacío) sin que
        // eso rompa la validación ni deje un valor previo obsoleto.
        $colegio   = $this->crearColegio($this->crearPlan());
        $ejecutivo = $this->crearEjecutivo($colegio);

        ConfiguracionColegio::set($colegio->idColegio, ConfiguracionColegio::MONTO_MENSUALIDAD, '99999');

        $this->actingAs($ejecutivo)->put('/configuracion', [
            'dia_disponibilidad'        => 1,
            'horas_limite_confirmacion' => 4,
        ])->assertRedirect();

        $this->assertSame(0.0, ConfiguracionColegio::getMontoMensualidad($colegio->idColegio));
    }

    public function test_dia_de_disponibilidad_fuera_de_rango_falla_validacion_y_no_cambia_nada(): void
    {
        $colegio   = $this->crearColegio($this->crearPlan());
        $ejecutivo = $this->crearEjecutivo($colegio);

        ConfiguracionColegio::set($colegio->idColegio, ConfiguracionColegio::DIA_DISPONIBILIDAD, '3');

        $response = $this->actingAs($ejecutivo)->put('/configuracion', [
            'dia_disponibilidad'        => 99,
            'horas_limite_confirmacion' => 4,
        ]);

        $response->assertSessionHasErrors('dia_disponibilidad');
        $this->assertSame(3, ConfiguracionColegio::getDiaDisponibilidad($colegio->idColegio));
    }

    public function test_monto_de_mensualidad_negativo_falla_validacion(): void
    {
        $colegio   = $this->crearColegio($this->crearPlan());
        $ejecutivo = $this->crearEjecutivo($colegio);

        $response = $this->actingAs($ejecutivo)->put('/configuracion', [
            'dia_disponibilidad'        => 1,
            'horas_limite_confirmacion' => 4,
            'monto_mensualidad'         => -100,
        ]);

        $response->assertSessionHasErrors('monto_mensualidad');
    }

    public function test_usuario_sin_permiso_no_puede_ver_ni_actualizar(): void
    {
        $colegio = $this->crearColegio($this->crearPlan());
        $usuario = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'arbitro']);

        $this->actingAs($usuario)->get('/configuracion')->assertForbidden();
        $this->actingAs($usuario)->get('/configuracion/colegio')->assertForbidden();
        $this->actingAs($usuario)->put('/configuracion', ['dia_disponibilidad' => 1, 'horas_limite_confirmacion' => 4])->assertForbidden();
    }

    public function test_subir_y_eliminar_logo(): void
    {
        Storage::fake('public');

        $colegio   = $this->crearColegio($this->crearPlan());
        $ejecutivo = $this->crearEjecutivo($colegio);

        // create() en vez de image(): no requiere la extensión GD del
        // entorno (image() renderiza un GIF/PNG real en memoria) — para
        // probar la validación 'image'/'mimes' basta un archivo con el MIME
        // correcto, no una imagen real decodificable.
        $archivo = UploadedFile::fake()->create('logo.png', 100, 'image/png');

        $response = $this->actingAs($ejecutivo)->post('/configuracion/logo', ['logo' => $archivo]);
        $response->assertRedirect();

        $colegio->refresh();
        $this->assertNotNull($colegio->logoColegio);
        Storage::disk('public')->assertExists($colegio->logoColegio);

        $rutaAnterior = $colegio->logoColegio;

        $response = $this->actingAs($ejecutivo)->delete('/configuracion/logo');
        $response->assertRedirect();

        $colegio->refresh();
        $this->assertNull($colegio->logoColegio);
        Storage::disk('public')->assertMissing($rutaAnterior);
    }
}

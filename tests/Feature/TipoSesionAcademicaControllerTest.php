<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Colegio;
use App\Models\SesionAcademica;
use App\Models\TipoSesionAcademica;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Cobertura HTTP de TipoSesionAcademicaController — Académico no tenía
 * ningún test de Feature (ver auditoría). También sirve de regresión para
 * CatalogoActivableController (base compartida con TipoSancionController).
 */
class TipoSesionAcademicaControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    private function crearColegioConAcademico(): Colegio
    {
        return $this->crearColegio($this->crearPlan(['modulosJSON' => ['arbitros', 'torneos', 'designaciones', 'academico']]));
    }

    private function crearInstructor(Colegio $colegio): User
    {
        foreach (['ver-academico', 'crear-academico', 'editar-academico', 'gestionar-asistencia'] as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        $rol = Role::firstOrCreate(['name' => 'tecnico', 'guard_name' => 'web']);
        $rol->syncPermissions(['ver-academico', 'crear-academico', 'editar-academico', 'gestionar-asistencia']);

        $usuario = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'tecnico']);
        $usuario->assignRole('tecnico');

        return $usuario;
    }

    private function crearTipoSesion(Colegio $colegio, array $overrides = []): TipoSesionAcademica
    {
        return TipoSesionAcademica::create(array_merge([
            'idColegio' => $colegio->idColegio,
            'etiqueta'  => 'Charla ' . uniqid(),
            'esOficial' => false,
            'esActivo'  => true,
        ], $overrides));
    }

    public function test_lista_los_tipos_del_colegio_ordenados(): void
    {
        $colegio    = $this->crearColegioConAcademico();
        $instructor = $this->crearInstructor($colegio);

        $this->crearTipoSesion($colegio, ['etiqueta' => 'b_charla', 'orden' => 2]);
        $this->crearTipoSesion($colegio, ['etiqueta' => 'a_charla', 'orden' => 1]);

        $this->actingAs($instructor)->get(route('tipos-sesion-academica.index'))
            ->assertOk()
            ->assertViewIs('academico.tipos')
            ->assertSeeInOrder(['a_charla', 'b_charla']);
    }

    public function test_crea_un_tipo_de_sesion(): void
    {
        $colegio    = $this->crearColegioConAcademico();
        $instructor = $this->crearInstructor($colegio);

        $this->actingAs($instructor)->post(route('tipos-sesion-academica.store'), [
            'etiqueta'  => 'Prueba FCF',
            'esOficial' => '1',
        ])->assertRedirect(route('tipos-sesion-academica.index'));

        $this->assertDatabaseHas('tipos_sesion_academica', [
            'idColegio' => $colegio->idColegio,
            'etiqueta'  => 'Prueba FCF',
            'esOficial' => true,
            'esActivo'  => true,
        ]);
    }

    public function test_alterna_el_estado_activo(): void
    {
        $colegio    = $this->crearColegioConAcademico();
        $instructor = $this->crearInstructor($colegio);
        $tipo       = $this->crearTipoSesion($colegio);

        $this->actingAs($instructor)->put(route('tipos-sesion-academica.estado', $tipo->idTipoSesion))
            ->assertRedirect(route('tipos-sesion-academica.index'));

        $this->assertFalse($tipo->fresh()->esActivo);
    }

    public function test_elimina_un_tipo_sin_sesiones_registradas(): void
    {
        $colegio    = $this->crearColegioConAcademico();
        $instructor = $this->crearInstructor($colegio);
        $tipo       = $this->crearTipoSesion($colegio);

        $this->actingAs($instructor)->delete(route('tipos-sesion-academica.destroy', $tipo->idTipoSesion))
            ->assertRedirect(route('tipos-sesion-academica.index'));

        $this->assertDatabaseMissing('tipos_sesion_academica', ['idTipoSesion' => $tipo->idTipoSesion]);
    }

    public function test_no_elimina_un_tipo_con_sesiones_registradas(): void
    {
        $colegio    = $this->crearColegioConAcademico();
        $instructor = $this->crearInstructor($colegio);
        $tipo       = $this->crearTipoSesion($colegio);

        SesionAcademica::create([
            'idColegio'        => $colegio->idColegio,
            'idTipoSesion'     => $tipo->idTipoSesion,
            'tema'             => 'Sesión de prueba',
            'fechaSesion'      => today()->addDay(),
            'horaSesion'       => '18:00',
            'duracionMinutos'  => 60,
            'modalidad'        => 'presencial',
            'dirigidaA'        => 'todos',
            'modoAsistencia'   => 'manual',
            'esObligatoria'    => false,
            'idInstructor'     => $instructor->idUsuario,
            'estadoSesion'     => SesionAcademica::ESTADO_PROGRAMADA,
        ]);

        $this->actingAs($instructor)->delete(route('tipos-sesion-academica.destroy', $tipo->idTipoSesion))
            ->assertRedirect();

        $this->assertDatabaseHas('tipos_sesion_academica', ['idTipoSesion' => $tipo->idTipoSesion]);
    }

    public function test_un_colegio_no_puede_gestionar_tipos_de_otro_colegio(): void
    {
        $colegioA    = $this->crearColegioConAcademico();
        $colegioB    = $this->crearColegioConAcademico();
        $instructorA = $this->crearInstructor($colegioA);
        $tipoB       = $this->crearTipoSesion($colegioB);

        $this->actingAs($instructorA)->put(route('tipos-sesion-academica.estado', $tipoB->idTipoSesion))
            ->assertForbidden();

        $this->actingAs($instructorA)->delete(route('tipos-sesion-academica.destroy', $tipoB->idTipoSesion))
            ->assertForbidden();
    }
}

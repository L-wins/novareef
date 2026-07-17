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
 * Cobertura de SesionAcademicaController — Académico no tenía ningún test de
 * Feature (ver auditoría de plataforma). Cubre el ciclo de vida completo de
 * una sesión: crear (con generación automática de asistencia esperada),
 * abrir, cerrar, cancelar, y las restricciones de editar/eliminar solo
 * mientras sigue programada.
 */
class AcademicoSesionFlujoTest extends TestCase
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

    private function crearTipoSesion(Colegio $colegio): TipoSesionAcademica
    {
        return TipoSesionAcademica::create([
            'idColegio' => $colegio->idColegio,
            'etiqueta'  => 'Charla técnica',
            'esActivo'  => true,
        ]);
    }

    private function datosSesion(TipoSesionAcademica $tipo): array
    {
        return [
            'idTipoSesion'    => $tipo->idTipoSesion,
            'modalidad'       => 'presencial',
            'tema'            => 'Reglas de juego 2026',
            'fechaSesion'     => today()->addDays(3)->format('Y-m-d'),
            'horaSesion'      => '18:00',
            'duracionMinutos' => 90,
            'dirigidaA'       => 'todos',
            'modoAsistencia'  => 'manual',
        ];
    }

    public function test_crear_sesion_genera_asistencia_esperada_para_cada_arbitro(): void
    {
        $colegio    = $this->crearColegioConAcademico();
        $instructor = $this->crearInstructor($colegio);
        $tipo       = $this->crearTipoSesion($colegio);
        $this->crearArbitro($colegio);
        $this->crearArbitro($colegio);

        $this->actingAs($instructor)->post(route('academico.sesiones.store'), $this->datosSesion($tipo))
            ->assertRedirect();

        $sesion = SesionAcademica::where('idColegio', $colegio->idColegio)->firstOrFail();

        $this->assertSame(SesionAcademica::ESTADO_PROGRAMADA, $sesion->estadoSesion);
        $this->assertSame(2, $sesion->asistencias()->count());
        $this->assertSame(2, $sesion->asistencias()->where('estadoAsistencia', 'ausente')->count());
    }

    public function test_solo_se_puede_editar_o_eliminar_mientras_esta_programada(): void
    {
        $colegio    = $this->crearColegioConAcademico();
        $instructor = $this->crearInstructor($colegio);
        $tipo       = $this->crearTipoSesion($colegio);

        $this->actingAs($instructor)->post(route('academico.sesiones.store'), $this->datosSesion($tipo));
        $sesion = SesionAcademica::where('idColegio', $colegio->idColegio)->firstOrFail();

        $this->actingAs($instructor)->get(route('academico.sesiones.edit', $sesion->idSesion))->assertOk();

        $this->actingAs($instructor)->put(route('academico.sesiones.abrir', $sesion->idSesion));
        $sesion->refresh();
        $this->assertSame(SesionAcademica::ESTADO_EN_CURSO, $sesion->estadoSesion);

        // Ya no está programada: editar y eliminar quedan bloqueados.
        $this->actingAs($instructor)->get(route('academico.sesiones.edit', $sesion->idSesion))->assertForbidden();
        $this->actingAs($instructor)->delete(route('academico.sesiones.destroy', $sesion->idSesion))->assertForbidden();
    }

    public function test_cerrar_confirma_la_asistencia_como_definitiva(): void
    {
        $colegio    = $this->crearColegioConAcademico();
        $instructor = $this->crearInstructor($colegio);
        $tipo       = $this->crearTipoSesion($colegio);

        $this->actingAs($instructor)->post(route('academico.sesiones.store'), $this->datosSesion($tipo));
        $sesion = SesionAcademica::where('idColegio', $colegio->idColegio)->firstOrFail();

        $this->actingAs($instructor)->put(route('academico.sesiones.abrir', $sesion->idSesion));

        $this->actingAs($instructor)->put(route('academico.sesiones.cerrar', $sesion->idSesion))
            ->assertRedirect(route('academico.sesiones.show', $sesion->idSesion));

        $this->assertSame(SesionAcademica::ESTADO_FINALIZADA, $sesion->fresh()->estadoSesion);
        $this->assertFalse((bool) $sesion->fresh()->sesionAbierta);
    }

    public function test_no_se_puede_cancelar_una_sesion_ya_finalizada(): void
    {
        $colegio    = $this->crearColegioConAcademico();
        $instructor = $this->crearInstructor($colegio);
        $tipo       = $this->crearTipoSesion($colegio);

        $this->actingAs($instructor)->post(route('academico.sesiones.store'), $this->datosSesion($tipo));
        $sesion = SesionAcademica::where('idColegio', $colegio->idColegio)->firstOrFail();

        $this->actingAs($instructor)->put(route('academico.sesiones.abrir', $sesion->idSesion));
        $this->actingAs($instructor)->put(route('academico.sesiones.cerrar', $sesion->idSesion));

        $this->actingAs($instructor)->put(route('academico.sesiones.cancelar', $sesion->idSesion))
            ->assertRedirect();

        $this->assertSame(SesionAcademica::ESTADO_FINALIZADA, $sesion->fresh()->estadoSesion);
    }

    public function test_un_colegio_no_puede_ver_sesiones_de_otro_colegio(): void
    {
        $colegioA    = $this->crearColegioConAcademico();
        $colegioB    = $this->crearColegioConAcademico();
        $instructorA = $this->crearInstructor($colegioA);
        $tipoB       = $this->crearTipoSesion($colegioB);

        $this->actingAs($this->crearInstructor($colegioB))->post(route('academico.sesiones.store'), $this->datosSesion($tipoB));
        $sesionB = SesionAcademica::where('idColegio', $colegioB->idColegio)->firstOrFail();

        $this->actingAs($instructorA)->get(route('academico.sesiones.show', $sesionB->idSesion))->assertForbidden();
    }

    public function test_el_arbitro_ve_sus_proximas_clases_en_mis_clases(): void
    {
        $colegio    = $this->crearColegioConAcademico();
        $instructor = $this->crearInstructor($colegio);
        $tipo       = $this->crearTipoSesion($colegio);
        $arbitro    = $this->crearArbitro($colegio);

        Permission::firstOrCreate(['name' => 'ver-academico', 'guard_name' => 'web']);
        $rolArbitro = Role::firstOrCreate(['name' => 'arbitro', 'guard_name' => 'web']);
        $rolArbitro->givePermissionTo('ver-academico');
        $arbitro->usuario->assignRole('arbitro');

        $this->actingAs($instructor)->post(route('academico.sesiones.store'), $this->datosSesion($tipo));

        $this->actingAs($arbitro->usuario)->get(route('academico.mis-clases'))
            ->assertOk()
            ->assertViewHas('proximas', fn ($proximas) => $proximas->count() === 1);
    }
}

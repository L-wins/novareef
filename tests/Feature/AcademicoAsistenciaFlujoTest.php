<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AsistenciaAcademica;
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
 * Cobertura de AsistenciaController — marca web del árbitro, registro por
 * scanner del instructor, y corrección manual — las tres formas en que
 * cambia una asistencia en tiempo real (ver AsistenciaActualizadaEvent).
 */
class AcademicoAsistenciaFlujoTest extends TestCase
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

    private function darPermisoArbitro(): void
    {
        Permission::firstOrCreate(['name' => 'ver-academico', 'guard_name' => 'web']);
        $rolArbitro = Role::firstOrCreate(['name' => 'arbitro', 'guard_name' => 'web']);
        $rolArbitro->givePermissionTo('ver-academico');
    }

    private function crearSesion(Colegio $colegio, User $instructor, string $modoAsistencia): SesionAcademica
    {
        $tipo = TipoSesionAcademica::create(['idColegio' => $colegio->idColegio, 'etiqueta' => 'Charla', 'esActivo' => true]);

        return SesionAcademica::create([
            'idColegio'       => $colegio->idColegio,
            'idInstructor'    => $instructor->idUsuario,
            'idTipoSesion'    => $tipo->idTipoSesion,
            'modalidad'       => 'presencial',
            'tema'            => 'Sesión de prueba',
            'fechaSesion'     => today(),
            'horaSesion'      => '18:00',
            'duracionMinutos' => 60,
            'dirigidaA'       => 'todos',
            'modoAsistencia'  => $modoAsistencia,
            'esObligatoria'   => true,
            'estadoSesion'    => SesionAcademica::ESTADO_PROGRAMADA,
            'sesionAbierta'   => false,
        ]);
    }

    private function crearAsistenciaEsperada(SesionAcademica $sesion, $arbitro): AsistenciaAcademica
    {
        return AsistenciaAcademica::create([
            'idColegio'            => $sesion->idColegio,
            'idSesion'             => $sesion->idSesion,
            'idArbitro'            => $arbitro->idArbitro,
            'estadoAsistencia'     => AsistenciaAcademica::ESTADO_AUSENTE,
            'registradoPor'        => AsistenciaAcademica::REGISTRADO_SISTEMA,
            'confirmadoInstructor' => false,
        ]);
    }

    public function test_el_arbitro_marca_su_propia_asistencia_en_modo_manual(): void
    {
        $colegio    = $this->crearColegioConAcademico();
        $instructor = $this->crearInstructor($colegio);
        $arbitro    = $this->crearArbitro($colegio);
        $this->darPermisoArbitro();
        $arbitro->usuario->assignRole('arbitro');

        $sesion      = $this->crearSesion($colegio, $instructor, 'manual');
        $asistencia  = $this->crearAsistenciaEsperada($sesion, $arbitro);

        // Sesión cerrada: no se puede marcar todavía.
        $this->actingAs($arbitro->usuario)->post(route('academico.asistencias.marcar', $asistencia->idAsistencia))
            ->assertSessionHas('error');
        $this->assertSame('ausente', $asistencia->fresh()->estadoAsistencia);

        $sesion->update(['sesionAbierta' => true]);

        $this->actingAs($arbitro->usuario)->post(route('academico.asistencias.marcar', $asistencia->idAsistencia))
            ->assertRedirect();

        $asistencia->refresh();
        $this->assertSame('presente', $asistencia->estadoAsistencia);
        $this->assertSame('arbitro', $asistencia->registradoPor);
    }

    public function test_un_arbitro_no_puede_marcar_la_asistencia_de_otro(): void
    {
        $colegio    = $this->crearColegioConAcademico();
        $instructor = $this->crearInstructor($colegio);
        $arbitroA   = $this->crearArbitro($colegio);
        $arbitroB   = $this->crearArbitro($colegio);
        $this->darPermisoArbitro();
        $arbitroB->usuario->assignRole('arbitro');

        $sesion     = $this->crearSesion($colegio, $instructor, 'manual');
        $asistencia = $this->crearAsistenciaEsperada($sesion, $arbitroA);

        $this->actingAs($arbitroB->usuario)->post(route('academico.asistencias.marcar', $asistencia->idAsistencia))
            ->assertNotFound();
    }

    public function test_registro_por_scanner_requiere_sesion_abierta_en_modo_scanner(): void
    {
        $colegio    = $this->crearColegioConAcademico();
        $instructor = $this->crearInstructor($colegio);
        $arbitro    = $this->crearArbitro($colegio);

        $sesion     = $this->crearSesion($colegio, $instructor, 'scanner');
        $asistencia = $this->crearAsistenciaEsperada($sesion, $arbitro);

        $payload = ['idSesion' => $sesion->idSesion, 'codigoCarnet' => $arbitro->codigoCarnet];

        $this->actingAs($instructor)->postJson(route('academico.scanner'), $payload)
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $sesion->update(['sesionAbierta' => true]);

        $this->actingAs($instructor)->postJson(route('academico.scanner'), $payload)
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('presente', $asistencia->fresh()->estadoAsistencia);
        $this->assertSame('instructor', $asistencia->fresh()->registradoPor);
    }

    public function test_el_instructor_corrige_una_marca_mientras_la_sesion_sigue_abierta(): void
    {
        $colegio    = $this->crearColegioConAcademico();
        $instructor = $this->crearInstructor($colegio);
        $arbitro    = $this->crearArbitro($colegio);

        $sesion     = $this->crearSesion($colegio, $instructor, 'manual');
        $sesion->update(['sesionAbierta' => true]);
        $asistencia = $this->crearAsistenciaEsperada($sesion, $arbitro);

        $this->actingAs($instructor)->putJson(route('academico.asistencias.corregir', $asistencia->idAsistencia), [
            'estadoAsistencia' => 'presente',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertSame('presente', $asistencia->fresh()->estadoAsistencia);

        $sesion->update(['sesionAbierta' => false]);

        $this->actingAs($instructor)->putJson(route('academico.asistencias.corregir', $asistencia->idAsistencia), [
            'estadoAsistencia' => 'ausente',
        ])->assertStatus(422)->assertJson(['success' => false]);
    }
}

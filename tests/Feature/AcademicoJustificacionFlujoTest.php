<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AsistenciaAcademica;
use App\Models\Colegio;
use App\Models\JustificacionAcademica;
use App\Models\SesionAcademica;
use App\Models\TipoSesionAcademica;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Cobertura de JustificacionController (crear, árbitro) y
 * JustificacionRevisionController (aprobar/rechazar, instructor/ejecutivo/
 * sanciones) — el flujo completo de justificar una inasistencia.
 */
class AcademicoJustificacionFlujoTest extends TestCase
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

    private function darPermisoArbitro($arbitro): void
    {
        Permission::firstOrCreate(['name' => 'ver-academico', 'guard_name' => 'web']);
        $rolArbitro = Role::firstOrCreate(['name' => 'arbitro', 'guard_name' => 'web']);
        $rolArbitro->givePermissionTo('ver-academico');
        $arbitro->usuario->assignRole('arbitro');
    }

    private function crearAsistenciaAusente(Colegio $colegio, User $instructor, $arbitro, array $overridesSesion = []): AsistenciaAcademica
    {
        $tipo = TipoSesionAcademica::create(['idColegio' => $colegio->idColegio, 'etiqueta' => 'Charla', 'esActivo' => true]);

        $sesion = SesionAcademica::create(array_merge([
            'idColegio'       => $colegio->idColegio,
            'idInstructor'    => $instructor->idUsuario,
            'idTipoSesion'    => $tipo->idTipoSesion,
            'modalidad'       => 'presencial',
            'tema'            => 'Sesión de prueba',
            'fechaSesion'     => today(),
            'horaSesion'      => '18:00',
            'duracionMinutos' => 60,
            'dirigidaA'       => 'todos',
            'modoAsistencia'  => 'manual',
            'esObligatoria'   => true,
            'estadoSesion'    => SesionAcademica::ESTADO_FINALIZADA,
            'sesionAbierta'   => false,
        ], $overridesSesion));

        return AsistenciaAcademica::create([
            'idColegio'            => $colegio->idColegio,
            'idSesion'             => $sesion->idSesion,
            'idArbitro'            => $arbitro->idArbitro,
            'estadoAsistencia'     => AsistenciaAcademica::ESTADO_AUSENTE,
            'registradoPor'        => AsistenciaAcademica::REGISTRADO_SISTEMA,
            'confirmadoInstructor' => true,
        ]);
    }

    public function test_el_arbitro_justifica_su_inasistencia_dentro_del_plazo(): void
    {
        $colegio    = $this->crearColegioConAcademico();
        $instructor = $this->crearInstructor($colegio);
        $arbitro    = $this->crearArbitro($colegio);
        $this->darPermisoArbitro($arbitro);

        $asistencia = $this->crearAsistenciaAusente($colegio, $instructor, $arbitro);

        $this->actingAs($arbitro->usuario)
            ->post(route('academico.justificaciones.store', $asistencia->idAsistencia), [
                'motivo' => 'Cita médica el mismo día de la sesión.',
            ])
            ->assertRedirect(route('academico.mis-clases'));

        $this->assertDatabaseHas('justificaciones_academicas', [
            'idAsistencia'        => $asistencia->idAsistencia,
            'estadoJustificacion' => JustificacionAcademica::ESTADO_PENDIENTE,
        ]);
        $this->assertSame('justificacion_pendiente', $asistencia->fresh()->estadoAsistencia);
    }

    public function test_no_se_puede_justificar_dos_veces_la_misma_inasistencia(): void
    {
        $colegio    = $this->crearColegioConAcademico();
        $instructor = $this->crearInstructor($colegio);
        $arbitro    = $this->crearArbitro($colegio);
        $this->darPermisoArbitro($arbitro);

        $asistencia = $this->crearAsistenciaAusente($colegio, $instructor, $arbitro);

        JustificacionAcademica::create([
            'idColegio'           => $colegio->idColegio,
            'idAsistencia'        => $asistencia->idAsistencia,
            'idArbitro'           => $arbitro->idArbitro,
            'motivo'              => 'Primera justificación',
            'estadoJustificacion' => JustificacionAcademica::ESTADO_PENDIENTE,
            'fechaLimite'         => today()->addDays(3),
        ]);

        $this->actingAs($arbitro->usuario)
            ->post(route('academico.justificaciones.store', $asistencia->idAsistencia), ['motivo' => 'Segundo intento'])
            ->assertSessionHas('error');

        $this->assertSame(1, JustificacionAcademica::where('idAsistencia', $asistencia->idAsistencia)->count());
    }

    public function test_no_se_puede_justificar_fuera_de_plazo(): void
    {
        $colegio    = $this->crearColegioConAcademico();
        $instructor = $this->crearInstructor($colegio);
        $arbitro    = $this->crearArbitro($colegio);
        $this->darPermisoArbitro($arbitro);

        $asistencia = $this->crearAsistenciaAusente($colegio, $instructor, $arbitro, [
            'fechaSesion' => today()->subDays(10),
        ]);

        $this->actingAs($arbitro->usuario)
            ->post(route('academico.justificaciones.store', $asistencia->idAsistencia), ['motivo' => 'Muy tarde'])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('justificaciones_academicas', ['idAsistencia' => $asistencia->idAsistencia]);
    }

    public function test_instructor_aprueba_una_justificacion_pendiente(): void
    {
        $colegio    = $this->crearColegioConAcademico();
        $instructor = $this->crearInstructor($colegio);
        $arbitro    = $this->crearArbitro($colegio);

        $asistencia = $this->crearAsistenciaAusente($colegio, $instructor, $arbitro);
        $justificacion = JustificacionAcademica::create([
            'idColegio'           => $colegio->idColegio,
            'idAsistencia'        => $asistencia->idAsistencia,
            'idArbitro'           => $arbitro->idArbitro,
            'motivo'              => 'Cita médica',
            'estadoJustificacion' => JustificacionAcademica::ESTADO_PENDIENTE,
            'fechaLimite'         => today()->addDays(3),
        ]);
        $asistencia->update(['estadoAsistencia' => 'justificacion_pendiente']);

        $this->actingAs($instructor)
            ->put(route('sanciones.justificaciones.revisar', $justificacion->idJustificacion), ['accion' => 'aprobar'])
            ->assertRedirect(route('sanciones.justificaciones.pendientes'));

        $this->assertSame(JustificacionAcademica::ESTADO_APROBADA, $justificacion->fresh()->estadoJustificacion);
        $this->assertSame('justificado', $asistencia->fresh()->estadoAsistencia);
    }

    public function test_rechazar_exige_motivo(): void
    {
        $colegio    = $this->crearColegioConAcademico();
        $instructor = $this->crearInstructor($colegio);
        $arbitro    = $this->crearArbitro($colegio);

        $asistencia = $this->crearAsistenciaAusente($colegio, $instructor, $arbitro);
        $justificacion = JustificacionAcademica::create([
            'idColegio'           => $colegio->idColegio,
            'idAsistencia'        => $asistencia->idAsistencia,
            'idArbitro'           => $arbitro->idArbitro,
            'motivo'              => 'Cita médica',
            'estadoJustificacion' => JustificacionAcademica::ESTADO_PENDIENTE,
            'fechaLimite'         => today()->addDays(3),
        ]);

        $this->actingAs($instructor)
            ->put(route('sanciones.justificaciones.revisar', $justificacion->idJustificacion), ['accion' => 'rechazar'])
            ->assertSessionHasErrors('motivoRechazo');

        $this->actingAs($instructor)
            ->put(route('sanciones.justificaciones.revisar', $justificacion->idJustificacion), [
                'accion'        => 'rechazar',
                'motivoRechazo' => 'No aporta soporte alguno.',
            ])
            ->assertRedirect();

        $this->assertSame(JustificacionAcademica::ESTADO_RECHAZADA, $justificacion->fresh()->estadoJustificacion);
        $this->assertSame('justificacion_rechazada', $asistencia->fresh()->estadoAsistencia);
    }
}

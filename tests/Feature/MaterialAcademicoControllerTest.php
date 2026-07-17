<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Colegio;
use App\Models\MaterialAcademico;
use App\Models\SesionAcademica;
use App\Models\TipoSesionAcademica;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Cobertura de MaterialAcademicoController — subir, eliminar y descargar
 * material de clase. El disco es 'local' (no accesible directo, a
 * diferencia de fotos de árbitro) y la descarga queda abierta a cualquiera
 * con ver-academico, no solo a quien lo subió.
 */
class MaterialAcademicoControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    private function crearColegioConAcademico(): Colegio
    {
        return $this->crearColegio($this->crearPlan(['modulosJSON' => ['arbitros', 'torneos', 'designaciones', 'academico']]));
    }

    private function crearInstructor(Colegio $colegio): User
    {
        foreach (['ver-academico', 'crear-academico', 'editar-academico'] as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        $rol = Role::firstOrCreate(['name' => 'tecnico', 'guard_name' => 'web']);
        $rol->syncPermissions(['ver-academico', 'crear-academico', 'editar-academico']);

        $usuario = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'tecnico']);
        $usuario->assignRole('tecnico');

        return $usuario;
    }

    private function crearSesion(Colegio $colegio, User $instructor): SesionAcademica
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
            'modoAsistencia'  => 'manual',
            'esObligatoria'   => true,
            'estadoSesion'    => SesionAcademica::ESTADO_PROGRAMADA,
            'sesionAbierta'   => false,
        ]);
    }

    public function test_el_instructor_sube_material_de_clase(): void
    {
        Storage::fake('local');

        $colegio    = $this->crearColegioConAcademico();
        $instructor = $this->crearInstructor($colegio);
        $sesion     = $this->crearSesion($colegio, $instructor);

        $archivo = UploadedFile::fake()->create('slides.pdf', 500, 'application/pdf');

        $this->actingAs($instructor)->post(route('academico.materiales.store', $sesion->idSesion), [
            'titulo'  => 'Diapositivas de la charla',
            'archivo' => $archivo,
        ])->assertRedirect();

        $this->assertDatabaseHas('materiales_academicos', [
            'idSesion' => $sesion->idSesion,
            'titulo'   => 'Diapositivas de la charla',
        ]);

        $material = MaterialAcademico::where('idSesion', $sesion->idSesion)->firstOrFail();
        Storage::disk('local')->assertExists($material->archivo);
    }

    public function test_cualquiera_con_ver_academico_puede_descargar_no_solo_quien_subio(): void
    {
        Storage::fake('local');

        $colegio    = $this->crearColegioConAcademico();
        $instructor = $this->crearInstructor($colegio);
        $sesion     = $this->crearSesion($colegio, $instructor);
        $arbitro    = $this->crearArbitro($colegio);

        Permission::firstOrCreate(['name' => 'ver-academico', 'guard_name' => 'web']);
        $rolArbitro = Role::firstOrCreate(['name' => 'arbitro', 'guard_name' => 'web']);
        $rolArbitro->givePermissionTo('ver-academico');
        $arbitro->usuario->assignRole('arbitro');

        $material = MaterialAcademico::create([
            'idColegio'      => $colegio->idColegio,
            'idSesion'       => $sesion->idSesion,
            'titulo'         => 'Guía de estudio',
            'archivo'        => UploadedFile::fake()->create('guia.pdf', 100)->store('materiales-academicos', 'local'),
            'extension'      => 'pdf',
            'idUsuarioSubio' => $instructor->idUsuario,
        ]);

        $this->actingAs($arbitro->usuario)->get(route('academico.materiales.descargar', $material->idMaterial))
            ->assertOk();
    }

    public function test_eliminar_material_borra_el_archivo_del_disco(): void
    {
        Storage::fake('local');

        $colegio    = $this->crearColegioConAcademico();
        $instructor = $this->crearInstructor($colegio);
        $sesion     = $this->crearSesion($colegio, $instructor);

        $ruta = UploadedFile::fake()->create('material.pdf', 100)->store('materiales-academicos', 'local');
        $material = MaterialAcademico::create([
            'idColegio'      => $colegio->idColegio,
            'idSesion'       => $sesion->idSesion,
            'titulo'         => 'Material a borrar',
            'archivo'        => $ruta,
            'extension'      => 'pdf',
            'idUsuarioSubio' => $instructor->idUsuario,
        ]);

        $this->actingAs($instructor)->delete(route('academico.materiales.destroy', $material->idMaterial))
            ->assertRedirect();

        $this->assertDatabaseMissing('materiales_academicos', ['idMaterial' => $material->idMaterial]);
        Storage::disk('local')->assertMissing($ruta);
    }

    public function test_un_colegio_no_puede_gestionar_material_de_otro_colegio(): void
    {
        Storage::fake('local');

        $colegioA    = $this->crearColegioConAcademico();
        $colegioB    = $this->crearColegioConAcademico();
        $instructorA = $this->crearInstructor($colegioA);
        $instructorB = $this->crearInstructor($colegioB);
        $sesionB     = $this->crearSesion($colegioB, $instructorB);

        $ruta = UploadedFile::fake()->create('material.pdf', 100)->store('materiales-academicos', 'local');
        $materialB = MaterialAcademico::create([
            'idColegio'      => $colegioB->idColegio,
            'idSesion'       => $sesionB->idSesion,
            'titulo'         => 'Material de otro colegio',
            'archivo'        => $ruta,
            'extension'      => 'pdf',
            'idUsuarioSubio' => $instructorB->idUsuario,
        ]);

        $this->actingAs($instructorA)->post(route('academico.materiales.store', $sesionB->idSesion), [
            'titulo'  => 'Intento cruzado',
            'archivo' => UploadedFile::fake()->create('x.pdf', 50),
        ])->assertForbidden();

        $this->actingAs($instructorA)->delete(route('academico.materiales.destroy', $materialB->idMaterial))
            ->assertForbidden();
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Colegio;
use App\Models\Sancion;
use App\Models\TipoSancion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Cobertura HTTP de TipoSancionController — antes de este archivo el
 * catálogo de tipos de sanción solo tenía cobertura de modelo (ver
 * SancionFlujoTest), nunca a nivel de ruta/controlador. También sirve de
 * regresión para CatalogoActivableController (base compartida con
 * TipoSesionAcademicaController).
 */
class TipoSancionControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    private function crearColegioConSanciones(): Colegio
    {
        return $this->crearColegio($this->crearPlan(['modulosJSON' => ['arbitros', 'torneos', 'designaciones', 'sanciones']]));
    }

    private function crearMiembroComite(Colegio $colegio): User
    {
        foreach (['ver-sanciones', 'crear-sanciones', 'editar-sanciones'] as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        $rol = Role::firstOrCreate(['name' => 'sanciones', 'guard_name' => 'web']);
        $rol->syncPermissions(['ver-sanciones', 'crear-sanciones', 'editar-sanciones']);

        $usuario = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'sanciones']);
        $usuario->assignRole('sanciones');

        return $usuario;
    }

    private function crearTipoSancion(Colegio $colegio, array $overrides = []): TipoSancion
    {
        return TipoSancion::create(array_merge([
            'idColegio' => $colegio->idColegio,
            'etiqueta'  => 'Falta de prueba ' . uniqid(),
            'severidad' => 'moderada',
            'esActivo'  => true,
        ], $overrides));
    }

    public function test_lista_los_tipos_del_colegio_ordenados(): void
    {
        $colegio = $this->crearColegioConSanciones();
        $comite  = $this->crearMiembroComite($colegio);

        $this->crearTipoSancion($colegio, ['etiqueta' => 'B falta', 'orden' => 2]);
        $this->crearTipoSancion($colegio, ['etiqueta' => 'A falta', 'orden' => 1]);

        $this->actingAs($comite)->get(route('tipos-sancion.index'))
            ->assertOk()
            ->assertViewIs('sanciones.tipos')
            ->assertSeeInOrder(['A falta', 'B falta']);
    }

    public function test_crea_un_tipo_de_sancion(): void
    {
        $colegio = $this->crearColegioConSanciones();
        $comite  = $this->crearMiembroComite($colegio);

        $this->actingAs($comite)->post(route('tipos-sancion.store'), [
            'etiqueta'  => 'Reincidencia',
            'severidad' => 'grave',
        ])->assertRedirect(route('tipos-sancion.index'));

        $this->assertDatabaseHas('tipos_sancion', [
            'idColegio' => $colegio->idColegio,
            'etiqueta'  => 'Reincidencia',
            'esActivo'  => true,
        ]);
    }

    public function test_crea_un_tipo_de_sancion_con_articulo_del_reglamento(): void
    {
        $colegio = $this->crearColegioConSanciones();
        $comite  = $this->crearMiembroComite($colegio);

        $this->actingAs($comite)->post(route('tipos-sancion.store'), [
            'etiqueta'           => 'Reincidencia',
            'articuloReglamento' => 'Art. 12 del Reglamento Interno',
            'severidad'          => 'grave',
        ])->assertRedirect(route('tipos-sancion.index'));

        $this->assertDatabaseHas('tipos_sancion', [
            'idColegio'          => $colegio->idColegio,
            'etiqueta'           => 'Reincidencia',
            'articuloReglamento' => 'Art. 12 del Reglamento Interno',
        ]);
    }

    public function test_alterna_el_estado_activo(): void
    {
        $colegio = $this->crearColegioConSanciones();
        $comite  = $this->crearMiembroComite($colegio);
        $tipo    = $this->crearTipoSancion($colegio);

        $this->actingAs($comite)->put(route('tipos-sancion.estado', $tipo->idTipoSancion))
            ->assertRedirect(route('tipos-sancion.index'));

        $this->assertFalse($tipo->fresh()->esActivo);
    }

    public function test_elimina_un_tipo_sin_sanciones_registradas(): void
    {
        $colegio = $this->crearColegioConSanciones();
        $comite  = $this->crearMiembroComite($colegio);
        $tipo    = $this->crearTipoSancion($colegio);

        $this->actingAs($comite)->delete(route('tipos-sancion.destroy', $tipo->idTipoSancion))
            ->assertRedirect(route('tipos-sancion.index'));

        $this->assertDatabaseMissing('tipos_sancion', ['idTipoSancion' => $tipo->idTipoSancion]);
    }

    public function test_no_elimina_un_tipo_con_sanciones_registradas(): void
    {
        $colegio = $this->crearColegioConSanciones();
        $comite  = $this->crearMiembroComite($colegio);
        $arbitro = $this->crearArbitro($colegio);
        $tipo    = $this->crearTipoSancion($colegio);

        Sancion::create([
            'idColegio'          => $colegio->idColegio,
            'idArbitro'          => $arbitro->idArbitro,
            'idTipoSancion'      => $tipo->idTipoSancion,
            'motivoSancion'      => 'Prueba',
            'fechaHecho'         => today(),
            'fechaInicioSancion' => today(),
            'estadoSancion'      => Sancion::ESTADO_ACTIVA,
            'idUsuarioImpuso'    => $comite->idUsuario,
            'version'            => 0,
        ]);

        $this->actingAs($comite)->delete(route('tipos-sancion.destroy', $tipo->idTipoSancion))
            ->assertRedirect();

        $this->assertDatabaseHas('tipos_sancion', ['idTipoSancion' => $tipo->idTipoSancion]);
    }

    public function test_un_colegio_no_puede_gestionar_tipos_de_otro_colegio(): void
    {
        $colegioA = $this->crearColegioConSanciones();
        $colegioB = $this->crearColegioConSanciones();
        $comiteA  = $this->crearMiembroComite($colegioA);
        $tipoB    = $this->crearTipoSancion($colegioB);

        $this->actingAs($comiteA)->put(route('tipos-sancion.estado', $tipoB->idTipoSancion))
            ->assertForbidden();

        $this->actingAs($comiteA)->delete(route('tipos-sancion.destroy', $tipoB->idTipoSancion))
            ->assertForbidden();
    }
}

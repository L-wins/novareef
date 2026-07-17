<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Arbitro;
use App\Models\CategoriaArbitro;
use App\Models\Colegio;
use App\Models\User;
use Database\Seeders\EstadoArbitroSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Cobertura de ArbitroController y CategoriaArbitroController — CRUD de
 * árbitro completo no tenía ningún test de Feature (ver auditoría de
 * plataforma).
 */
class ArbitroCrudControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    private function crearEjecutivoConPermisos(Colegio $colegio): User
    {
        foreach (['ver-arbitros', 'crear-arbitros', 'editar-arbitros'] as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        $rol = Role::firstOrCreate(['name' => 'ejecutivo', 'guard_name' => 'web']);
        $rol->syncPermissions(['ver-arbitros', 'crear-arbitros', 'editar-arbitros']);

        // ArbitroService::registrarConCredenciales hace assignRole('arbitro')
        // internamente al registrar uno nuevo — el rol debe existir de antemano.
        Role::firstOrCreate(['name' => 'arbitro', 'guard_name' => 'web']);

        $usuario = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'ejecutivo']);
        $usuario->assignRole('ejecutivo');

        return $usuario;
    }

    private function datosNuevoArbitro(Colegio $colegio): array
    {
        $categoria = CategoriaArbitro::where('idColegio', $colegio->idColegio)->firstOrFail();

        return [
            'nombreUsuario'       => 'Juan Pérez',
            'emailUsuario'        => 'juan.perez.' . uniqid() . '@test.com',
            'telefonoUsuario'     => '3001234567',
            'idCategoria'         => $categoria->idCategoria,
            'tipoDocumento'       => 'cedula',
            'numeroDocumento'     => (string) random_int(10000000, 99999999),
            'fechaIngresoColegio' => today()->format('Y-m-d'),
        ];
    }

    public function test_lista_arbitros_del_colegio_con_filtros(): void
    {
        $colegio  = $this->crearColegio();
        $ejecutivo = $this->crearEjecutivoConPermisos($colegio);
        $this->crearArbitro($colegio);
        $this->crearArbitro($colegio);

        $this->actingAs($ejecutivo)->get(route('arbitros.index'))
            ->assertOk()
            ->assertViewHas('arbitros', fn ($arbitros) => $arbitros->total() === 2);
    }

    public function test_registra_un_arbitro_nuevo(): void
    {
        $colegio   = $this->crearColegio();
        $ejecutivo = $this->crearEjecutivoConPermisos($colegio);

        $this->actingAs($ejecutivo)->post(route('arbitros.store'), $this->datosNuevoArbitro($colegio))
            ->assertRedirect();

        $this->assertDatabaseHas('usuarios', [
            'idColegio'  => $colegio->idColegio,
            'rolUsuario' => 'arbitro',
        ]);
    }

    public function test_no_se_puede_registrar_al_alcanzar_el_limite_del_plan(): void
    {
        $plan     = $this->crearPlan(['limiteArbitros' => 1]);
        $colegio  = $this->crearColegio($plan);
        $ejecutivo = $this->crearEjecutivoConPermisos($colegio);
        $this->crearArbitro($colegio);

        $this->actingAs($ejecutivo)->get(route('arbitros.create'))
            ->assertRedirect(route('arbitros.index'));

        $this->actingAs($ejecutivo)->post(route('arbitros.store'), $this->datosNuevoArbitro($colegio))
            ->assertSessionHas('error');

        $this->assertSame(1, Arbitro::where('idColegio', $colegio->idColegio)->count());
    }

    public function test_actualiza_datos_del_arbitro(): void
    {
        $colegio   = $this->crearColegio();
        $ejecutivo = $this->crearEjecutivoConPermisos($colegio);
        $arbitro   = $this->crearArbitro($colegio);

        $this->actingAs($ejecutivo)->put(route('arbitros.update', $arbitro->idArbitro), [
            'nombreUsuario'   => 'Nombre Actualizado',
            'emailUsuario'    => $arbitro->usuario->emailUsuario,
            'telefonoUsuario' => '3009999999',
            'idCategoria'     => $arbitro->idCategoria,
            'tipoDocumento'   => $arbitro->tipoDocumento,
            'numeroDocumento' => $arbitro->numeroDocumento,
        ])->assertRedirect(route('arbitros.show', $arbitro->idArbitro));

        $this->assertSame('Nombre Actualizado', $arbitro->usuario->fresh()->nombreUsuario);
    }

    public function test_cambia_el_estado_del_arbitro_con_motivo(): void
    {
        $this->seed(EstadoArbitroSeeder::class);
        $colegio   = $this->crearColegio();
        $ejecutivo = $this->crearEjecutivoConPermisos($colegio);
        $arbitro   = $this->crearArbitro($colegio, ['arbitro' => ['estadoArbitro' => 'activo']]);

        $this->actingAs($ejecutivo)->put(route('arbitros.estado', $arbitro->idArbitro), [
            'estadoNuevo' => 'suspendido',
            'motivo'      => 'Sanción disciplinaria',
            'fechaInicio' => today()->format('Y-m-d'),
        ])->assertRedirect();

        $this->assertSame('suspendido', $arbitro->fresh()->estadoArbitro);
    }

    public function test_suspender_sin_motivo_falla_validacion(): void
    {
        $this->seed(EstadoArbitroSeeder::class);
        $colegio   = $this->crearColegio();
        $ejecutivo = $this->crearEjecutivoConPermisos($colegio);
        $arbitro   = $this->crearArbitro($colegio, ['arbitro' => ['estadoArbitro' => 'activo']]);

        $this->actingAs($ejecutivo)->put(route('arbitros.estado', $arbitro->idArbitro), [
            'estadoNuevo' => 'suspendido',
        ])->assertSessionHasErrors(['motivo', 'fechaInicio']);

        $this->assertSame('activo', $arbitro->fresh()->estadoArbitro);
    }

    public function test_archiva_y_restaura_un_arbitro(): void
    {
        $colegio   = $this->crearColegio();
        $ejecutivo = $this->crearEjecutivoConPermisos($colegio);
        $arbitro   = $this->crearArbitro($colegio);

        $this->actingAs($ejecutivo)->post(route('arbitros.archivar', $arbitro->idArbitro), [
            'motivo' => 'Se retira del colegio',
        ])->assertRedirect(route('arbitros.index'));

        $this->assertSoftDeleted('arbitros', ['idArbitro' => $arbitro->idArbitro]);

        $this->actingAs($ejecutivo)->get(route('arbitros.archivados'))
            ->assertOk()
            ->assertViewHas('arbitros', fn ($arbitros) => $arbitros->total() === 1);

        $this->actingAs($ejecutivo)->post(route('arbitros.restaurar', $arbitro->idArbitro))
            ->assertRedirect(route('arbitros.show', $arbitro->idArbitro));

        $this->assertNull($arbitro->fresh()->deleted_at);
    }

    public function test_un_colegio_no_puede_ver_ni_editar_arbitros_de_otro_colegio(): void
    {
        $colegioA  = $this->crearColegio();
        $colegioB  = $this->crearColegio();
        $ejecutivoA = $this->crearEjecutivoConPermisos($colegioA);
        $arbitroB   = $this->crearArbitro($colegioB);

        $this->actingAs($ejecutivoA)->get(route('arbitros.show', $arbitroB->idArbitro))->assertNotFound();
        $this->actingAs($ejecutivoA)->get(route('arbitros.edit', $arbitroB->idArbitro))->assertNotFound();
    }

    public function test_lista_crea_activa_y_elimina_categorias(): void
    {
        $colegio   = $this->crearColegio();
        $ejecutivo = $this->crearEjecutivoConPermisos($colegio);

        $this->actingAs($ejecutivo)->get(route('categorias.arbitro.index'))->assertOk();

        $this->actingAs($ejecutivo)->post(route('categorias.arbitro.store'), [
            'nombreCategoria' => 'Segunda categoría',
        ])->assertRedirect(route('categorias.arbitro.index'));

        $categoria = CategoriaArbitro::where('idColegio', $colegio->idColegio)
            ->where('nombreCategoria', 'Segunda categoría')
            ->firstOrFail();

        $this->actingAs($ejecutivo)->put(route('categorias.arbitro.estado', $categoria->idCategoria))
            ->assertRedirect(route('categorias.arbitro.index'));
        $this->assertFalse($categoria->fresh()->activa);

        $this->actingAs($ejecutivo)->delete(route('categorias.arbitro.destroy', $categoria->idCategoria))
            ->assertRedirect(route('categorias.arbitro.index'));
        $this->assertDatabaseMissing('categorias_arbitro', ['idCategoria' => $categoria->idCategoria]);
    }

    public function test_no_elimina_una_categoria_con_arbitros_asignados(): void
    {
        $colegio   = $this->crearColegio();
        $ejecutivo = $this->crearEjecutivoConPermisos($colegio);
        $arbitro   = $this->crearArbitro($colegio);

        $this->actingAs($ejecutivo)->delete(route('categorias.arbitro.destroy', $arbitro->idCategoria))
            ->assertRedirect();

        $this->assertDatabaseHas('categorias_arbitro', ['idCategoria' => $arbitro->idCategoria]);
    }
}

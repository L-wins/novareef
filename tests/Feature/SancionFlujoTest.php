<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Colegio;
use App\Models\MovimientoFinanciero;
use App\Models\Sancion;
use App\Models\TipoSancion;
use App\Models\User;
use App\Services\SancionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

class SancionFlujoTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    private function crearColegioConSanciones(): Colegio
    {
        return $this->crearColegio($this->crearPlan(['modulosJSON' => ['arbitros', 'torneos', 'designaciones', 'sanciones', 'finanzas']]));
    }

    private function crearMiembroComite(Colegio $colegio): User
    {
        foreach (['ver-sanciones', 'crear-sanciones', 'editar-sanciones', 'ver-finanzas'] as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        $rol = Role::firstOrCreate(['name' => 'sanciones', 'guard_name' => 'web']);
        $rol->syncPermissions(['ver-sanciones', 'crear-sanciones', 'editar-sanciones', 'ver-finanzas']);

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

    public function test_registra_una_sancion_sin_multa(): void
    {
        $colegio  = $this->crearColegioConSanciones();
        $comite   = $this->crearMiembroComite($colegio);
        $arbitro  = $this->crearArbitro($colegio);
        $tipo     = $this->crearTipoSancion($colegio);

        $response = $this->actingAs($comite)->post('/sanciones', [
            'idArbitro'          => $arbitro->idArbitro,
            'idTipoSancion'      => $tipo->idTipoSancion,
            'motivoSancion'      => 'Llegó tarde al partido',
            'fechaHecho'         => today()->format('Y-m-d'),
            'fechaInicioSancion' => today()->format('Y-m-d'),
        ]);

        $response->assertRedirect();

        $sancion = Sancion::where('idColegio', $colegio->idColegio)->firstOrFail();
        $this->assertSame('activa', $sancion->estadoSancion);
        $this->assertFalse((bool) $sancion->tieneMultaEconomica);
        $this->assertNull($sancion->idMovimientoFinanciero);
        $this->assertSame(1, $sancion->historial()->count());
    }

    public function test_registra_una_sancion_con_multa_economica_y_genera_el_movimiento(): void
    {
        $colegio = $this->crearColegioConSanciones();
        $comite  = $this->crearMiembroComite($colegio);
        $arbitro = $this->crearArbitro($colegio);
        $tipo    = $this->crearTipoSancion($colegio);

        $response = $this->actingAs($comite)->post('/sanciones', [
            'idArbitro'           => $arbitro->idArbitro,
            'idTipoSancion'       => $tipo->idTipoSancion,
            'motivoSancion'       => 'Inasistencia injustificada',
            'fechaHecho'          => today()->format('Y-m-d'),
            'fechaInicioSancion'  => today()->format('Y-m-d'),
            'tieneMultaEconomica' => '1',
            'montoMulta'          => 25000,
        ]);

        $response->assertRedirect();

        $sancion = Sancion::where('idColegio', $colegio->idColegio)->firstOrFail();
        $this->assertTrue((bool) $sancion->tieneMultaEconomica);
        $this->assertNotNull($sancion->idMovimientoFinanciero);

        $movimiento = $sancion->movimientoFinanciero;
        $this->assertSame(MovimientoFinanciero::CATEGORIA_MULTA, $movimiento->categoria);
        $this->assertSame(MovimientoFinanciero::ORIGEN_MULTA_SANCION, $movimiento->tipoOrigenMulta);
        $this->assertSame($sancion->idSancion, $movimiento->idOrigenMulta);
        $this->assertSame(25000.0, (float) $movimiento->montoTotal);
        $this->assertSame($arbitro->idArbitro, $movimiento->idArbitro);
    }

    public function test_cumplir_una_sancion_activa(): void
    {
        $colegio  = $this->crearColegioConSanciones();
        $comite   = $this->crearMiembroComite($colegio);
        $arbitro  = $this->crearArbitro($colegio);
        $servicio = app(SancionService::class);

        $sancion = $servicio->crearSancion($colegio->idColegio, [
            'idArbitro' => $arbitro->idArbitro,
            'idTipoSancion' => $this->crearTipoSancion($colegio)->idTipoSancion,
            'motivoSancion' => 'Motivo', 'fechaHecho' => today()->format('Y-m-d'),
            'fechaInicioSancion' => today()->format('Y-m-d'), 'tieneMultaEconomica' => false,
        ], $comite);

        $this->actingAs($comite)->put("/sanciones/{$sancion->idSancion}/estado", [
            'accion' => 'cumplir',
        ])->assertRedirect();

        $this->assertSame('cumplida', $sancion->fresh()->estadoSancion);
    }

    public function test_anular_una_sancion_requiere_permiso_editar_sanciones_y_motivo(): void
    {
        $colegio  = $this->crearColegioConSanciones();
        $arbitro  = $this->crearArbitro($colegio);
        $servicio = app(SancionService::class);

        // Rol con crear-sanciones pero SIN editar-sanciones.
        foreach (['ver-sanciones', 'crear-sanciones'] as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }
        $rol = Role::firstOrCreate(['name' => 'designador-sin-editar', 'guard_name' => 'web']);
        $rol->syncPermissions(['ver-sanciones', 'crear-sanciones']);
        $usuarioSinEditar = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'designador']);
        $usuarioSinEditar->assignRole('designador-sin-editar');

        $sancion = $servicio->crearSancion($colegio->idColegio, [
            'idArbitro' => $arbitro->idArbitro,
            'idTipoSancion' => $this->crearTipoSancion($colegio)->idTipoSancion,
            'motivoSancion' => 'Motivo', 'fechaHecho' => today()->format('Y-m-d'),
            'fechaInicioSancion' => today()->format('Y-m-d'), 'tieneMultaEconomica' => false,
        ], $usuarioSinEditar);

        $this->actingAs($usuarioSinEditar)->put("/sanciones/{$sancion->idSancion}/estado", [
            'accion' => 'anular', 'motivo' => 'Registro duplicado',
        ])->assertForbidden();

        $this->assertSame('activa', $sancion->fresh()->estadoSancion);

        $comite = $this->crearMiembroComite($colegio);
        $this->actingAs($comite)->put("/sanciones/{$sancion->idSancion}/estado", [
            'accion' => 'anular', 'motivo' => 'Registro duplicado',
        ])->assertRedirect();

        $this->assertSame('anulada', $sancion->fresh()->estadoSancion);
    }

    public function test_anular_una_sancion_con_multa_sin_abonos_anula_tambien_el_movimiento(): void
    {
        $colegio  = $this->crearColegioConSanciones();
        $comite   = $this->crearMiembroComite($colegio);
        $arbitro  = $this->crearArbitro($colegio);
        $servicio = app(SancionService::class);

        $sancion = $servicio->crearSancion($colegio->idColegio, [
            'idArbitro' => $arbitro->idArbitro,
            'idTipoSancion' => $this->crearTipoSancion($colegio)->idTipoSancion,
            'motivoSancion' => 'Motivo', 'fechaHecho' => today()->format('Y-m-d'),
            'fechaInicioSancion' => today()->format('Y-m-d'),
            'tieneMultaEconomica' => true, 'montoMulta' => 10000,
        ], $comite);

        $servicio->anular($sancion, $comite, 'Error de registro');

        $sancion->refresh();
        $this->assertSame('anulada', $sancion->estadoSancion);
        $this->assertSame('anulado', $sancion->movimientoFinanciero->fresh()->estadoMovimiento);
    }

    public function test_apelar_y_resolver_apelacion_confirmada_deja_la_sancion_cumplida(): void
    {
        $colegio  = $this->crearColegioConSanciones();
        $comite   = $this->crearMiembroComite($colegio);
        $arbitro  = $this->crearArbitro($colegio);
        $servicio = app(SancionService::class);

        $sancion = $servicio->crearSancion($colegio->idColegio, [
            'idArbitro' => $arbitro->idArbitro,
            'idTipoSancion' => $this->crearTipoSancion($colegio)->idTipoSancion,
            'motivoSancion' => 'Motivo', 'fechaHecho' => today()->format('Y-m-d'),
            'fechaInicioSancion' => today()->format('Y-m-d'), 'tieneMultaEconomica' => false,
        ], $comite);

        $servicio->apelar($sancion, $comite, 'El árbitro apela');
        $this->assertSame('apelada', $sancion->fresh()->estadoSancion);

        $servicio->resolverApelacion($sancion, 'confirmada', $comite);
        $this->assertSame('cumplida', $sancion->fresh()->estadoSancion);
    }

    public function test_resolver_apelacion_revocada_anula_la_sancion(): void
    {
        $colegio  = $this->crearColegioConSanciones();
        $comite   = $this->crearMiembroComite($colegio);
        $arbitro  = $this->crearArbitro($colegio);
        $servicio = app(SancionService::class);

        $sancion = $servicio->crearSancion($colegio->idColegio, [
            'idArbitro' => $arbitro->idArbitro,
            'idTipoSancion' => $this->crearTipoSancion($colegio)->idTipoSancion,
            'motivoSancion' => 'Motivo', 'fechaHecho' => today()->format('Y-m-d'),
            'fechaInicioSancion' => today()->format('Y-m-d'), 'tieneMultaEconomica' => false,
        ], $comite);

        $servicio->apelar($sancion, $comite);
        $servicio->resolverApelacion($sancion, 'revocada', $comite);

        $this->assertSame('anulada', $sancion->fresh()->estadoSancion);
    }

    public function test_un_arbitro_solo_ve_sus_propias_sanciones(): void
    {
        $colegio  = $this->crearColegioConSanciones();
        $comite   = $this->crearMiembroComite($colegio);
        $arbitroA = $this->crearArbitro($colegio);
        $arbitroB = $this->crearArbitro($colegio);
        $servicio = app(SancionService::class);
        $tipo     = $this->crearTipoSancion($colegio);

        Permission::firstOrCreate(['name' => 'ver-sanciones', 'guard_name' => 'web']);
        $rolArbitro = Role::firstOrCreate(['name' => 'arbitro', 'guard_name' => 'web']);
        $rolArbitro->givePermissionTo('ver-sanciones');
        $arbitroB->usuario->assignRole('arbitro');

        $sancionA = $servicio->crearSancion($colegio->idColegio, [
            'idArbitro' => $arbitroA->idArbitro, 'idTipoSancion' => $tipo->idTipoSancion,
            'motivoSancion' => 'De A', 'fechaHecho' => today()->format('Y-m-d'),
            'fechaInicioSancion' => today()->format('Y-m-d'), 'tieneMultaEconomica' => false,
        ], $comite);

        $sancionB = $servicio->crearSancion($colegio->idColegio, [
            'idArbitro' => $arbitroB->idArbitro, 'idTipoSancion' => $tipo->idTipoSancion,
            'motivoSancion' => 'De B', 'fechaHecho' => today()->format('Y-m-d'),
            'fechaInicioSancion' => today()->format('Y-m-d'), 'tieneMultaEconomica' => false,
        ], $comite);

        $response = $this->actingAs($arbitroB->usuario)->get('/sanciones');
        $response->assertOk();
        $response->assertSee(route('sanciones.show', $sancionB->idSancion));
        $response->assertDontSee(route('sanciones.show', $sancionA->idSancion));

        $this->actingAs($arbitroB->usuario)->get("/sanciones/{$sancionA->idSancion}")->assertForbidden();
        $this->actingAs($arbitroB->usuario)->get("/sanciones/{$sancionB->idSancion}")->assertOk();
    }

    public function test_un_colegio_no_puede_ver_sanciones_de_otro_colegio(): void
    {
        $colegioA = $this->crearColegioConSanciones();
        $colegioB = $this->crearColegioConSanciones();
        $comiteA  = $this->crearMiembroComite($colegioA);
        $arbitroB = $this->crearArbitro($colegioB);
        $servicio = app(SancionService::class);

        $sancionB = $servicio->crearSancion($colegioB->idColegio, [
            'idArbitro' => $arbitroB->idArbitro,
            'idTipoSancion' => $this->crearTipoSancion($colegioB)->idTipoSancion,
            'motivoSancion' => 'De otro colegio', 'fechaHecho' => today()->format('Y-m-d'),
            'fechaInicioSancion' => today()->format('Y-m-d'), 'tieneMultaEconomica' => false,
        ], $this->crearMiembroComite($colegioB));

        $this->actingAs($comiteA)->get("/sanciones/{$sancionB->idSancion}")->assertForbidden();
    }

    public function test_tipos_de_sancion_son_por_colegio_no_globales(): void
    {
        $colegioA = $this->crearColegioConSanciones();
        $colegioB = $this->crearColegioConSanciones();

        $this->crearTipoSancion($colegioA, ['etiqueta' => 'Grave A']);
        $this->crearTipoSancion($colegioB, ['etiqueta' => 'Grave B']);

        $this->assertSame(1, TipoSancion::where('idColegio', $colegioA->idColegio)->count());
        $this->assertSame(1, TipoSancion::where('idColegio', $colegioB->idColegio)->count());
    }

    public function test_un_arbitro_sancionado_puede_seguir_siendo_designado(): void
    {
        // Decisión de negocio explícita: Sanciones es un registro puro,
        // nunca bloquea designaciones (revierte lo que decía references/modulos.md).
        $datos    = $this->prepararPartidoPublicado();
        $servicio = app(SancionService::class);
        $comite   = $this->crearMiembroComite($datos['colegio']);

        $sancion = $servicio->crearSancion($datos['colegio']->idColegio, [
            'idArbitro' => $datos['arbitroCentral']->idArbitro,
            'idTipoSancion' => $this->crearTipoSancion($datos['colegio'])->idTipoSancion,
            'motivoSancion' => 'Sanción grave', 'fechaHecho' => today()->format('Y-m-d'),
            'fechaInicioSancion' => today()->format('Y-m-d'),
            'fechaFinSancion' => today()->addMonth()->format('Y-m-d'),
            'tieneMultaEconomica' => false,
        ], $comite);

        $this->assertSame('activa', $sancion->estadoSancion);

        // El árbitro sancionado sigue confirmando su designación sin ningún bloqueo.
        $this->actingAs($datos['arbitroCentral']->usuario)
            ->postJson("/mis-partidos/{$datos['designacionCentral']->idDesignacion}/confirmar")
            ->assertJson(['success' => true]);
    }

    public function test_una_sancion_puede_ser_solo_economica_sin_rango_de_suspension(): void
    {
        $colegio = $this->crearColegioConSanciones();
        $comite  = $this->crearMiembroComite($colegio);
        $arbitro = $this->crearArbitro($colegio);
        $tipo    = $this->crearTipoSancion($colegio);

        $response = $this->actingAs($comite)->post('/sanciones', [
            'idArbitro'           => $arbitro->idArbitro,
            'idTipoSancion'       => $tipo->idTipoSancion,
            'motivoSancion'       => 'Solo una multa, sin suspensión',
            'fechaHecho'          => today()->format('Y-m-d'),
            'tieneMultaEconomica' => '1',
            'montoMulta'          => 30000,
        ]);

        $response->assertRedirect();

        $sancion = Sancion::where('idColegio', $colegio->idColegio)->firstOrFail();
        $this->assertNull($sancion->fechaInicioSancion);
        $this->assertNull($sancion->fechaFinSancion);
        $this->assertFalse($sancion->tieneSuspension());
        $this->assertTrue((bool) $sancion->tieneMultaEconomica);
    }

    public function test_no_se_puede_registrar_fecha_fin_sin_fecha_inicio_de_suspension(): void
    {
        $colegio = $this->crearColegioConSanciones();
        $comite  = $this->crearMiembroComite($colegio);
        $arbitro = $this->crearArbitro($colegio);
        $tipo    = $this->crearTipoSancion($colegio);

        $this->actingAs($comite)->post('/sanciones', [
            'idArbitro'       => $arbitro->idArbitro,
            'idTipoSancion'   => $tipo->idTipoSancion,
            'motivoSancion'   => 'Motivo',
            'fechaHecho'      => today()->format('Y-m-d'),
            'fechaFinSancion' => today()->addDays(10)->format('Y-m-d'),
        ])->assertSessionHasErrors('fechaInicioSancion');

        $this->assertDatabaseCount('sanciones', 0);
    }

    /** Asigna el rol Spatie 'arbitro' (con ver-sanciones) al usuario del árbitro — mismo patrón que test_un_arbitro_solo_ve_sus_propias_sanciones. */
    private function asignarRolArbitro(\App\Models\Arbitro $arbitro): void
    {
        Permission::firstOrCreate(['name' => 'ver-sanciones', 'guard_name' => 'web']);
        $rolArbitro = Role::firstOrCreate(['name' => 'arbitro', 'guard_name' => 'web']);
        $rolArbitro->givePermissionTo('ver-sanciones');
        $arbitro->usuario->assignRole('arbitro');
    }

    public function test_no_se_puede_apelar_una_sancion_fuera_del_plazo(): void
    {
        $colegio  = $this->crearColegioConSanciones();
        $comite   = $this->crearMiembroComite($colegio);
        $arbitro  = $this->crearArbitro($colegio);
        $servicio = app(SancionService::class);
        $this->asignarRolArbitro($arbitro);

        $sancion = $servicio->crearSancion($colegio->idColegio, [
            'idArbitro' => $arbitro->idArbitro,
            'idTipoSancion' => $this->crearTipoSancion($colegio)->idTipoSancion,
            'motivoSancion' => 'Motivo', 'fechaHecho' => today()->format('Y-m-d'),
            'tieneMultaEconomica' => false,
        ], $comite);

        $sancion->forceFill(['created_at' => now()->subDays(Sancion::DIAS_LIMITE_APELACION + 1)])->save();

        $this->assertFalse($sancion->fresh()->puedeApelarse());

        $this->actingAs($arbitro->usuario)->put("/sanciones/{$sancion->idSancion}/estado", [
            'accion' => 'apelar',
            'motivo' => 'Quiero apelar esta sanción',
        ])->assertSessionHas('error');

        $this->assertSame('activa', $sancion->fresh()->estadoSancion);
    }

    public function test_el_arbitro_dueno_puede_apelar_su_propia_sancion_activa(): void
    {
        $colegio  = $this->crearColegioConSanciones();
        $comite   = $this->crearMiembroComite($colegio);
        $arbitro  = $this->crearArbitro($colegio);
        $servicio = app(SancionService::class);
        $this->asignarRolArbitro($arbitro);

        $sancion = $servicio->crearSancion($colegio->idColegio, [
            'idArbitro' => $arbitro->idArbitro,
            'idTipoSancion' => $this->crearTipoSancion($colegio)->idTipoSancion,
            'motivoSancion' => 'Motivo', 'fechaHecho' => today()->format('Y-m-d'),
            'tieneMultaEconomica' => false,
        ], $comite);

        $this->actingAs($arbitro->usuario)->put("/sanciones/{$sancion->idSancion}/estado", [
            'accion' => 'apelar',
            'motivo' => 'No estuve de acuerdo con el reporte del veedor',
        ])->assertRedirect(route('sanciones.show', $sancion->idSancion));

        $this->assertSame('apelada', $sancion->fresh()->estadoSancion);
    }

    public function test_un_arbitro_que_no_es_el_dueno_no_puede_apelar_la_sancion_de_otro(): void
    {
        $colegio  = $this->crearColegioConSanciones();
        $comite   = $this->crearMiembroComite($colegio);
        $arbitroA = $this->crearArbitro($colegio);
        $arbitroB = $this->crearArbitro($colegio);
        $servicio = app(SancionService::class);
        $this->asignarRolArbitro($arbitroA);
        $this->asignarRolArbitro($arbitroB);

        $sancionA = $servicio->crearSancion($colegio->idColegio, [
            'idArbitro' => $arbitroA->idArbitro,
            'idTipoSancion' => $this->crearTipoSancion($colegio)->idTipoSancion,
            'motivoSancion' => 'Motivo', 'fechaHecho' => today()->format('Y-m-d'),
            'tieneMultaEconomica' => false,
        ], $comite);

        $this->actingAs($arbitroB->usuario)->put("/sanciones/{$sancionA->idSancion}/estado", [
            'accion' => 'apelar',
            'motivo' => 'Intento apelar la sanción de otro árbitro',
        ])->assertForbidden();

        $this->assertSame('activa', $sancionA->fresh()->estadoSancion);
    }

    public function test_el_comite_ya_no_puede_apelar_en_nombre_del_arbitro(): void
    {
        $colegio  = $this->crearColegioConSanciones();
        $comite   = $this->crearMiembroComite($colegio);
        $arbitro  = $this->crearArbitro($colegio);
        $servicio = app(SancionService::class);
        $this->asignarRolArbitro($arbitro);

        $sancion = $servicio->crearSancion($colegio->idColegio, [
            'idArbitro' => $arbitro->idArbitro,
            'idTipoSancion' => $this->crearTipoSancion($colegio)->idTipoSancion,
            'motivoSancion' => 'Motivo', 'fechaHecho' => today()->format('Y-m-d'),
            'tieneMultaEconomica' => false,
        ], $comite);

        $this->actingAs($comite)->put("/sanciones/{$sancion->idSancion}/estado", [
            'accion' => 'apelar',
            'motivo' => 'El comité intenta apelar en nombre del árbitro',
        ])->assertForbidden();

        $this->assertSame('activa', $sancion->fresh()->estadoSancion);
    }

    public function test_apelar_via_http_exige_motivo_obligatorio(): void
    {
        $colegio  = $this->crearColegioConSanciones();
        $comite   = $this->crearMiembroComite($colegio);
        $arbitro  = $this->crearArbitro($colegio);
        $servicio = app(SancionService::class);
        $this->asignarRolArbitro($arbitro);

        $sancion = $servicio->crearSancion($colegio->idColegio, [
            'idArbitro' => $arbitro->idArbitro,
            'idTipoSancion' => $this->crearTipoSancion($colegio)->idTipoSancion,
            'motivoSancion' => 'Motivo', 'fechaHecho' => today()->format('Y-m-d'),
            'tieneMultaEconomica' => false,
        ], $comite);

        $this->actingAs($arbitro->usuario)->put("/sanciones/{$sancion->idSancion}/estado", [
            'accion' => 'apelar',
        ])->assertSessionHasErrors('motivo');

        $this->assertSame('activa', $sancion->fresh()->estadoSancion);
    }

    public function test_se_marca_reincidente_al_tercer_sancion_en_el_mismo_semestre(): void
    {
        $colegio  = $this->crearColegioConSanciones();
        $comite   = $this->crearMiembroComite($colegio);
        $arbitro  = $this->crearArbitro($colegio);
        $servicio = app(SancionService::class);
        $tipo     = $this->crearTipoSancion($colegio);

        $datosBase = [
            'idArbitro' => $arbitro->idArbitro,
            'idTipoSancion' => $tipo->idTipoSancion,
            'motivoSancion' => 'Motivo', 'tieneMultaEconomica' => false,
        ];

        $servicio->crearSancion($colegio->idColegio, $datosBase + ['fechaHecho' => today()->subMonths(4)->format('Y-m-d')], $comite);
        $servicio->crearSancion($colegio->idColegio, $datosBase + ['fechaHecho' => today()->subMonths(2)->format('Y-m-d')], $comite);
        $tercera = $servicio->crearSancion($colegio->idColegio, $datosBase + ['fechaHecho' => today()->format('Y-m-d')], $comite);

        $this->assertTrue($servicio->esReincidente($tercera));
        $this->assertSame(3, $servicio->totalSancionesRecientes($tercera));

        $this->actingAs($comite)->get("/sanciones/{$tercera->idSancion}")
            ->assertOk()
            ->assertViewHas('esReincidente', true)
            ->assertSee('reincidente');
    }

    public function test_una_sancion_anulada_no_cuenta_para_reincidencia(): void
    {
        $colegio  = $this->crearColegioConSanciones();
        $comite   = $this->crearMiembroComite($colegio);
        $arbitro  = $this->crearArbitro($colegio);
        $servicio = app(SancionService::class);
        $tipo     = $this->crearTipoSancion($colegio);

        $datosBase = [
            'idArbitro' => $arbitro->idArbitro,
            'idTipoSancion' => $tipo->idTipoSancion,
            'motivoSancion' => 'Motivo', 'tieneMultaEconomica' => false,
        ];

        $anulada = $servicio->crearSancion($colegio->idColegio, $datosBase + ['fechaHecho' => today()->subMonth()->format('Y-m-d')], $comite);
        $servicio->anular($anulada, $comite, 'Error de registro');

        $segunda = $servicio->crearSancion($colegio->idColegio, $datosBase + ['fechaHecho' => today()->format('Y-m-d')], $comite);

        $this->assertFalse($servicio->esReincidente($segunda));
        $this->assertSame(1, $servicio->totalSancionesRecientes($segunda));
    }

    public function test_descarga_el_acta_pdf_de_una_sancion(): void
    {
        $colegio  = $this->crearColegioConSanciones();
        $comite   = $this->crearMiembroComite($colegio);
        $arbitro  = $this->crearArbitro($colegio);
        $servicio = app(SancionService::class);

        $sancion = $servicio->crearSancion($colegio->idColegio, [
            'idArbitro' => $arbitro->idArbitro,
            'idTipoSancion' => $this->crearTipoSancion($colegio, ['articuloReglamento' => 'Art. 12 del Reglamento Interno'])->idTipoSancion,
            'motivoSancion' => 'Motivo', 'fechaHecho' => today()->format('Y-m-d'),
            'tieneMultaEconomica' => false,
        ], $comite);

        $this->actingAs($comite)->get("/sanciones/{$sancion->idSancion}/acta")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_un_arbitro_no_puede_descargar_el_acta_de_otro_arbitro(): void
    {
        $colegio  = $this->crearColegioConSanciones();
        $comite   = $this->crearMiembroComite($colegio);
        $arbitroA = $this->crearArbitro($colegio);
        $arbitroB = $this->crearArbitro($colegio);
        $servicio = app(SancionService::class);

        Permission::firstOrCreate(['name' => 'ver-sanciones', 'guard_name' => 'web']);
        $rolArbitro = Role::firstOrCreate(['name' => 'arbitro', 'guard_name' => 'web']);
        $rolArbitro->givePermissionTo('ver-sanciones');
        $arbitroB->usuario->assignRole('arbitro');

        $sancionA = $servicio->crearSancion($colegio->idColegio, [
            'idArbitro' => $arbitroA->idArbitro,
            'idTipoSancion' => $this->crearTipoSancion($colegio)->idTipoSancion,
            'motivoSancion' => 'Motivo', 'fechaHecho' => today()->format('Y-m-d'),
            'tieneMultaEconomica' => false,
        ], $comite);

        $this->actingAs($arbitroB->usuario)->get("/sanciones/{$sancionA->idSancion}/acta")->assertForbidden();
    }
}

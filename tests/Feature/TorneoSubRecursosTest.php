<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Colegio;
use App\Models\DivisionTorneo;
use App\Models\EmergenteTorneo;
use App\Models\Partido;
use App\Models\SedeTorneo;
use App\Models\TarifaTorneo;
use App\Models\Torneo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Cobertura de los sub-recursos de Torneo — División, Sede, Tarifa,
 * Emergente — sin tests de Feature previos más allá del wiring incidental
 * de otros tests (crearDivision()/crearSede() del trait compartido, nunca
 * a través de HTTP).
 */
class TorneoSubRecursosTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    private function crearEjecutivo(Colegio $colegio): User
    {
        foreach (['ver-torneos', 'crear-torneos', 'editar-torneos', 'crear-designaciones'] as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        $rol = Role::firstOrCreate(['name' => 'ejecutivo', 'guard_name' => 'web']);
        $rol->syncPermissions(['ver-torneos', 'crear-torneos', 'editar-torneos', 'crear-designaciones']);

        $usuario = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'ejecutivo']);
        $usuario->assignRole('ejecutivo');

        return $usuario;
    }

    // ── Divisiones ──────────────────

    public function test_crea_actualiza_y_elimina_una_division(): void
    {
        $colegio   = $this->crearColegio();
        $ejecutivo = $this->crearEjecutivo($colegio);
        $torneo    = $this->crearTorneo($colegio, $ejecutivo);

        $this->actingAs($ejecutivo)->post(route('torneos.divisiones.store', $torneo->idTorneo), [
            'nombreDivision' => 'Primera',
        ])->assertRedirect();

        $division = DivisionTorneo::where('idTorneo', $torneo->idTorneo)->firstOrFail();

        $this->actingAs($ejecutivo)->put(route('torneos.divisiones.update', $division->idDivision), [
            'nombreDivision' => 'Primera A',
        ])->assertRedirect();
        $this->assertSame('Primera A', $division->fresh()->nombreDivision);

        $this->actingAs($ejecutivo)->delete(route('torneos.divisiones.destroy', $division->idDivision))
            ->assertRedirect();
        $this->assertDatabaseMissing('divisiones_torneo', ['idDivision' => $division->idDivision]);
    }

    public function test_no_elimina_una_division_con_partidos(): void
    {
        $colegio   = $this->crearColegio();
        $ejecutivo = $this->crearEjecutivo($colegio);
        $torneo    = $this->crearTorneo($colegio, $ejecutivo);
        $division  = $this->crearDivision($torneo);
        $sede      = $this->crearSede($torneo);

        Partido::create([
            'idColegio' => $colegio->idColegio, 'idTorneo' => $torneo->idTorneo,
            'idDivision' => $division->idDivision, 'idSede' => $sede->idSede,
            'idFormato' => $this->crearFormatoDupla()->idFormato,
            'equipoLocal' => 'A', 'equipoVisitante' => 'B',
            'fechaPartido' => today(), 'horaPartido' => '10:00',
            'modalidadPago' => 'campo', 'estadoPartido' => Partido::ESTADO_BORRADOR,
        ]);

        $this->actingAs($ejecutivo)->delete(route('torneos.divisiones.destroy', $division->idDivision))
            ->assertStatus(422);

        $this->assertDatabaseHas('divisiones_torneo', ['idDivision' => $division->idDivision]);
    }

    public function test_un_colegio_no_puede_editar_division_de_otro(): void
    {
        $colegioA  = $this->crearColegio();
        $colegioB  = $this->crearColegio();
        $ejecutivoA = $this->crearEjecutivo($colegioA);
        $ejecutivoB = $this->crearEjecutivo($colegioB);
        $torneoB    = $this->crearTorneo($colegioB, $ejecutivoB);
        $divisionB  = $this->crearDivision($torneoB);

        $this->actingAs($ejecutivoA)->put(route('torneos.divisiones.update', $divisionB->idDivision), [
            'nombreDivision' => 'Intento cruzado',
        ])->assertForbidden();
    }

    // ── Sedes ──────────────────

    public function test_crea_actualiza_y_elimina_una_sede(): void
    {
        $colegio   = $this->crearColegio();
        $ejecutivo = $this->crearEjecutivo($colegio);
        $torneo    = $this->crearTorneo($colegio, $ejecutivo);

        $datos = [
            'nombreSede' => 'Coliseo Central',
            'ciudad'     => 'Tenjo',
        ];

        $this->actingAs($ejecutivo)->post(route('torneos.sedes.store', $torneo->idTorneo), $datos)
            ->assertRedirect();

        $sede = SedeTorneo::where('idTorneo', $torneo->idTorneo)->firstOrFail();

        $this->actingAs($ejecutivo)->put(route('torneos.sedes.update', $sede->idSede), array_merge($datos, [
            'nombreSede' => 'Coliseo Renovado',
        ]))->assertRedirect();
        $this->assertSame('Coliseo Renovado', $sede->fresh()->nombreSede);

        $this->actingAs($ejecutivo)->delete(route('torneos.sedes.destroy', $sede->idSede))->assertRedirect();
        $this->assertDatabaseMissing('sedes_torneo', ['idSede' => $sede->idSede]);
    }

    public function test_no_elimina_una_sede_con_partidos(): void
    {
        $colegio   = $this->crearColegio();
        $ejecutivo = $this->crearEjecutivo($colegio);
        $torneo    = $this->crearTorneo($colegio, $ejecutivo);
        $division  = $this->crearDivision($torneo);
        $sede      = $this->crearSede($torneo);

        Partido::create([
            'idColegio' => $colegio->idColegio, 'idTorneo' => $torneo->idTorneo,
            'idDivision' => $division->idDivision, 'idSede' => $sede->idSede,
            'idFormato' => $this->crearFormatoDupla()->idFormato,
            'equipoLocal' => 'A', 'equipoVisitante' => 'B',
            'fechaPartido' => today(), 'horaPartido' => '10:00',
            'modalidadPago' => 'campo', 'estadoPartido' => Partido::ESTADO_BORRADOR,
        ]);

        $this->actingAs($ejecutivo)->delete(route('torneos.sedes.destroy', $sede->idSede))
            ->assertStatus(422);
    }

    // ── Tarifas ──────────────────

    public function test_crea_y_actualiza_una_tarifa(): void
    {
        $colegio   = $this->crearColegio();
        $ejecutivo = $this->crearEjecutivo($colegio);
        $torneo    = $this->crearTorneo($colegio, $ejecutivo);
        $division  = $this->crearDivision($torneo);
        $this->crearRolesPartido();
        $formato = $this->crearFormatoDupla();

        $this->actingAs($ejecutivo)->post(route('torneos.divisiones.tarifas.store', $division->idDivision), [
            'idRol'     => $this->idRolPorNombre('Central'),
            'idFormato' => $formato->idFormato,
            'valorPago' => 60000,
        ])->assertRedirect();

        $tarifa = TarifaTorneo::where('idDivision', $division->idDivision)->firstOrFail();
        $this->assertEquals(60000, $tarifa->valorPago);

        $this->actingAs($ejecutivo)->put(route('torneos.divisiones.tarifas.update', $tarifa->idTarifa), [
            'valorPago' => 65000,
        ])->assertRedirect();
        $this->assertEquals(65000, $tarifa->fresh()->valorPago);

        $this->actingAs($ejecutivo)->delete(route('torneos.divisiones.tarifas.destroy', $tarifa->idTarifa))->assertRedirect();
        $this->assertDatabaseMissing('tarifas_torneo', ['idTarifa' => $tarifa->idTarifa]);
    }

    public function test_guardar_la_misma_tarifa_dos_veces_actualiza_en_vez_de_duplicar(): void
    {
        $colegio   = $this->crearColegio();
        $ejecutivo = $this->crearEjecutivo($colegio);
        $torneo    = $this->crearTorneo($colegio, $ejecutivo);
        $division  = $this->crearDivision($torneo);
        $this->crearRolesPartido();
        $formato = $this->crearFormatoDupla();

        $payload = [
            'idRol'     => $this->idRolPorNombre('Central'),
            'idFormato' => $formato->idFormato,
            'valorPago' => 60000,
        ];

        $this->actingAs($ejecutivo)->post(route('torneos.divisiones.tarifas.store', $division->idDivision), $payload);
        $this->actingAs($ejecutivo)->post(route('torneos.divisiones.tarifas.store', $division->idDivision), array_merge($payload, ['valorPago' => 70000]));

        $this->assertSame(1, TarifaTorneo::where('idDivision', $division->idDivision)->count());
        $this->assertEquals(70000, TarifaTorneo::where('idDivision', $division->idDivision)->value('valorPago'));
    }

    // ── Emergentes ──────────────────

    public function test_lista_asigna_y_elimina_un_emergente(): void
    {
        $colegio   = $this->crearColegio();
        $ejecutivo = $this->crearEjecutivo($colegio);
        $torneo    = $this->crearTorneo($colegio, $ejecutivo);
        $sede      = $this->crearSede($torneo);
        $arbitro   = $this->crearArbitro($colegio, ['arbitro' => ['estadoArbitro' => 'activo']]);

        $this->actingAs($ejecutivo)->get(route('torneos.emergentes.index', $torneo->idTorneo))->assertOk();

        $this->actingAs($ejecutivo)->post(route('torneos.emergentes.store', $torneo->idTorneo), [
            'idArbitro'      => $arbitro->idArbitro,
            'idSede'         => $sede->idSede,
            'fechaEmergente' => today()->addDay()->format('Y-m-d'),
        ])->assertRedirect();

        $emergente = EmergenteTorneo::where('idTorneo', $torneo->idTorneo)->firstOrFail();
        $this->assertSame($arbitro->idArbitro, $emergente->idArbitro);

        $this->actingAs($ejecutivo)->delete(route('torneos.emergentes.destroy', [$torneo->idTorneo, $emergente->idEmergente]))
            ->assertRedirect();
        $this->assertDatabaseMissing('emergentes_torneo', ['idEmergente' => $emergente->idEmergente]);
    }

    public function test_un_colegio_no_puede_gestionar_emergentes_de_otro(): void
    {
        $colegioA   = $this->crearColegio();
        $colegioB   = $this->crearColegio();
        $ejecutivoA = $this->crearEjecutivo($colegioA);
        $ejecutivoB = $this->crearEjecutivo($colegioB);
        $torneoB    = $this->crearTorneo($colegioB, $ejecutivoB);

        $this->actingAs($ejecutivoA)->get(route('torneos.emergentes.index', $torneoB->idTorneo))
            ->assertForbidden();
    }
}

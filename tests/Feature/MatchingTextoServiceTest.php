<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Importacion\MatchingTextoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

class MatchingTextoServiceTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    public function test_matchea_division_exacta_ignorando_mayusculas_tildes_y_espacios(): void
    {
        $colegio  = $this->crearColegio();
        $creador  = $this->crearCuentaAdmin($colegio, 'ejecutivo');
        $torneo   = $this->crearTorneo($colegio, $creador);
        $division = $this->crearDivision($torneo);
        $division->update(['nombreDivision' => 'Primera C']);

        $matcher     = new MatchingTextoService();
        $divisiones  = $matcher->divisionesDelTorneo($torneo->idTorneo);

        $this->assertSame(
            $division->idDivision,
            $matcher->matchear('  primera   c  ', $divisiones),
        );
    }

    public function test_no_matchea_division_inexistente(): void
    {
        $colegio  = $this->crearColegio();
        $creador  = $this->crearCuentaAdmin($colegio, 'ejecutivo');
        $torneo   = $this->crearTorneo($colegio, $creador);
        $this->crearDivision($torneo)->update(['nombreDivision' => 'Primera C']);

        $matcher    = new MatchingTextoService();
        $divisiones = $matcher->divisionesDelTorneo($torneo->idTorneo);

        $this->assertNull($matcher->matchear('Segunda D', $divisiones));
    }

    public function test_matchea_sede_exacta_ignorando_tildes(): void
    {
        $colegio = $this->crearColegio();
        $creador = $this->crearCuentaAdmin($colegio, 'ejecutivo');
        $torneo  = $this->crearTorneo($colegio, $creador);
        $sede    = $this->crearSede($torneo);
        $sede->update(['nombreSede' => 'Cancha San José']);

        $matcher = new MatchingTextoService();
        $sedes   = $matcher->sedesDelTorneo($torneo->idTorneo);

        $this->assertSame($sede->idSede, $matcher->matchear('CANCHA SAN JOSE', $sedes));
    }

    public function test_no_mezcla_divisiones_de_otro_torneo(): void
    {
        $colegio = $this->crearColegio();
        $creador = $this->crearCuentaAdmin($colegio, 'ejecutivo');
        $torneoA = $this->crearTorneo($colegio, $creador);
        $torneoB = $this->crearTorneo($colegio, $creador);
        $this->crearDivision($torneoB)->update(['nombreDivision' => 'Primera C']);

        $matcher    = new MatchingTextoService();
        $divisiones = $matcher->divisionesDelTorneo($torneoA->idTorneo);

        $this->assertNull($matcher->matchear('Primera C', $divisiones));
    }

    public function test_sugiere_el_candidato_mas_parecido_cuando_no_hay_match_exacto(): void
    {
        $colegio  = $this->crearColegio();
        $creador  = $this->crearCuentaAdmin($colegio, 'ejecutivo');
        $torneo   = $this->crearTorneo($colegio, $creador);
        $sede     = $this->crearSede($torneo);
        $sede->update(['nombreSede' => 'Estadio Metropolitano']);

        $matcher = new MatchingTextoService();
        $sedes   = $matcher->sedesDelTorneo($torneo->idTorneo);

        $resultado = $matcher->matchearConSugerencia('Estadio Metropolitan', $sedes);

        $this->assertNull($resultado['idMatch']);
        $this->assertNotNull($resultado['sugerencia']);
        $this->assertSame($sede->idSede, $resultado['sugerencia']['id']);
        $this->assertGreaterThan(70, $resultado['sugerencia']['similitud']);
    }

    public function test_no_sugiere_nada_si_ningun_candidato_supera_el_umbral_de_similitud(): void
    {
        $colegio = $this->crearColegio();
        $creador = $this->crearCuentaAdmin($colegio, 'ejecutivo');
        $torneo  = $this->crearTorneo($colegio, $creador);
        $this->crearSede($torneo)->update(['nombreSede' => 'Cancha Norte']);

        $matcher = new MatchingTextoService();
        $sedes   = $matcher->sedesDelTorneo($torneo->idTorneo);

        $resultado = $matcher->matchearConSugerencia('Coliseo Central Sur', $sedes);

        $this->assertNull($resultado['idMatch']);
        $this->assertNull($resultado['sugerencia']);
    }

    public function test_matchear_con_sugerencia_devuelve_match_exacto_directo_sin_sugerencia(): void
    {
        $colegio  = $this->crearColegio();
        $creador  = $this->crearCuentaAdmin($colegio, 'ejecutivo');
        $torneo   = $this->crearTorneo($colegio, $creador);
        $division = $this->crearDivision($torneo);
        $division->update(['nombreDivision' => 'Primera C']);

        $matcher    = new MatchingTextoService();
        $divisiones = $matcher->divisionesDelTorneo($torneo->idTorneo);

        $resultado = $matcher->matchearConSugerencia('primera c', $divisiones);

        $this->assertSame($division->idDivision, $resultado['idMatch']);
        $this->assertNull($resultado['sugerencia']);
    }

    public function test_arbitros_del_colegio_excluye_suspendidos_y_solo_trae_el_colegio_activo(): void
    {
        $colegioA = $this->crearColegio();
        $colegioB = $this->crearColegio();

        $activo      = $this->crearArbitro($colegioA);
        $suspendido  = $this->crearArbitro($colegioA, ['arbitro' => ['estadoArbitro' => 'suspendido']]);
        $otroColegio = $this->crearArbitro($colegioB);

        $matcher   = new MatchingTextoService();
        $arbitros  = $matcher->arbitrosDelColegio($colegioA->idColegio);
        $idsListados = array_column($arbitros, 'id');

        $this->assertContains($activo->idArbitro, $idsListados);
        $this->assertNotContains($suspendido->idArbitro, $idsListados);
        $this->assertNotContains($otroColegio->idArbitro, $idsListados);
    }

    public function test_matchea_arbitro_por_nombre_exacto_dentro_del_colegio(): void
    {
        $colegio = $this->crearColegio();
        $arbitro = $this->crearArbitro($colegio, ['usuario' => ['nombreUsuario' => 'Juan Pérez']]);

        $matcher  = new MatchingTextoService();
        $arbitros = $matcher->arbitrosDelColegio($colegio->idColegio);

        $this->assertSame($arbitro->idArbitro, $matcher->matchear('JUAN PEREZ', $arbitros));
    }
}

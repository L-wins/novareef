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
}

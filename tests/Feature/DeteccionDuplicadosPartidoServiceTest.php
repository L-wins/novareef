<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\DesignacionService;
use App\Services\Importacion\DeteccionDuplicadosPartidoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

class DeteccionDuplicadosPartidoServiceTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    public function test_detecta_duplicado_por_torneo_division_equipos_y_fecha(): void
    {
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignador($colegio);
        $this->crearRolesPartido();
        $formato  = $this->crearFormatoDupla();
        $torneo   = $this->crearTorneo($colegio, $designador);
        $division = $this->crearDivision($torneo);
        $sede     = $this->crearSede($torneo);

        app(DesignacionService::class)->crearPartido($colegio->idColegio, [
            'idTorneo'        => $torneo->idTorneo,
            'idDivision'      => $division->idDivision,
            'idSede'          => $sede->idSede,
            'idFormato'       => $formato->idFormato,
            'equipoLocal'     => 'Santa Fe',
            'equipoVisitante' => 'Bethel',
            'fechaPartido'    => '2026-03-07',
            'horaPartido'     => '09:00',
            'observaciones'   => null,
        ], $designador->idUsuario);

        $servicio   = new DeteccionDuplicadosPartidoService();
        $existentes = $servicio->existentesDelTorneo($torneo->idTorneo);

        $this->assertTrue($servicio->esPosibleDuplicado(
            $existentes, $division->idDivision, 'SANTA FE', 'BETHEL', '2026-03-07',
        ));
    }

    public function test_hora_distinta_sigue_contando_como_duplicado(): void
    {
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignador($colegio);
        $this->crearRolesPartido();
        $formato  = $this->crearFormatoDupla();
        $torneo   = $this->crearTorneo($colegio, $designador);
        $division = $this->crearDivision($torneo);
        $sede     = $this->crearSede($torneo);

        app(DesignacionService::class)->crearPartido($colegio->idColegio, [
            'idTorneo' => $torneo->idTorneo, 'idDivision' => $division->idDivision,
            'idSede' => $sede->idSede, 'idFormato' => $formato->idFormato,
            'equipoLocal' => 'Santa Fe', 'equipoVisitante' => 'Bethel',
            'fechaPartido' => '2026-03-07', 'horaPartido' => '09:00', 'observaciones' => null,
        ], $designador->idUsuario);

        $servicio   = new DeteccionDuplicadosPartidoService();
        $existentes = $servicio->existentesDelTorneo($torneo->idTorneo);

        // Reprogramación de horario menor -> sigue siendo "el mismo partido".
        $this->assertTrue($servicio->esPosibleDuplicado(
            $existentes, $division->idDivision, 'Santa Fe', 'Bethel', '2026-03-07',
        ));
    }

    public function test_no_marca_duplicado_si_la_division_es_distinta(): void
    {
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignador($colegio);
        $this->crearRolesPartido();
        $formato   = $this->crearFormatoDupla();
        $torneo    = $this->crearTorneo($colegio, $designador);
        $division1 = $this->crearDivision($torneo);
        $division2 = $this->crearDivision($torneo);
        $sede      = $this->crearSede($torneo);

        app(DesignacionService::class)->crearPartido($colegio->idColegio, [
            'idTorneo' => $torneo->idTorneo, 'idDivision' => $division1->idDivision,
            'idSede' => $sede->idSede, 'idFormato' => $formato->idFormato,
            'equipoLocal' => 'Santa Fe', 'equipoVisitante' => 'Bethel',
            'fechaPartido' => '2026-03-07', 'horaPartido' => '09:00', 'observaciones' => null,
        ], $designador->idUsuario);

        $servicio   = new DeteccionDuplicadosPartidoService();
        $existentes = $servicio->existentesDelTorneo($torneo->idTorneo);

        $this->assertFalse($servicio->esPosibleDuplicado(
            $existentes, $division2->idDivision, 'Santa Fe', 'Bethel', '2026-03-07',
        ));
    }

    public function test_no_marca_duplicado_sin_division_o_sin_fecha(): void
    {
        $servicio = new DeteccionDuplicadosPartidoService();

        $this->assertFalse($servicio->esPosibleDuplicado([], null, 'Santa Fe', 'Bethel', '2026-03-07'));
        $this->assertFalse($servicio->esPosibleDuplicado([], 1, 'Santa Fe', 'Bethel', null));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ConfiguracionColegio;
use App\Models\DisponibilidadArbitro;
use App\Services\DisponibilidadService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * "No disponible" debe quedar guardado como un registro explícito (franja
 * 'no_disponible'), distinto de no tener ningún registro (sin reportar aún)
 * — antes de este fix, "No disponible" simplemente borraba la fila.
 */
class DisponibilidadServiceTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    private DisponibilidadService $disponibilidad;

    protected function setUp(): void
    {
        parent::setUp();
        $this->disponibilidad = app(DisponibilidadService::class);
    }

    /**
     * Red de seguridad para cualquier test que congele Carbon::setTestNow():
     * si una excepción (esperada o no) corta la ejecución antes del reset
     * manual, este tearDown evita que el "ahora" congelado se filtre a los
     * tests siguientes de la suite.
     */
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_guarda_una_franja_real_para_el_dia_reportado(): void
    {
        $colegio = $this->crearColegio($this->crearPlan());
        $arbitro = $this->crearArbitro($colegio);

        $this->disponibilidad->guardarSemana($arbitro, [
            ['fecha' => '2026-07-04', 'franja' => DisponibilidadArbitro::FRANJA_TODO_DIA],
        ]);

        $registro = DisponibilidadArbitro::where('idArbitro', $arbitro->idArbitro)->first();
        $this->assertSame(DisponibilidadArbitro::FRANJA_TODO_DIA, $registro->franjaHoraria);
        $this->assertTrue($registro->esDisponible());
    }

    public function test_no_disponible_se_guarda_explicito_no_se_borra(): void
    {
        $colegio = $this->crearColegio($this->crearPlan());
        $arbitro = $this->crearArbitro($colegio);

        $this->disponibilidad->guardarSemana($arbitro, [
            ['fecha' => '2026-07-04', 'franja' => DisponibilidadArbitro::FRANJA_NO_DISPONIBLE],
        ]);

        $registro = DisponibilidadArbitro::where('idArbitro', $arbitro->idArbitro)
            ->where('fechaDisponibilidad', '2026-07-04')
            ->first();

        $this->assertNotNull($registro, 'El día marcado "no disponible" debe quedar como registro real, no borrarse.');
        $this->assertFalse($registro->esDisponible());
        $this->assertSame('No disponible', $registro->franjaLegible());
    }

    public function test_dia_sin_franja_no_crea_ningun_registro(): void
    {
        $colegio = $this->crearColegio($this->crearPlan());
        $arbitro = $this->crearArbitro($colegio);

        $this->disponibilidad->guardarSemana($arbitro, [
            ['fecha' => '2026-07-04', 'franja' => null],
            ['fecha' => '2026-07-05', 'franja' => ''],
        ]);

        $this->assertSame(0, DisponibilidadArbitro::where('idArbitro', $arbitro->idArbitro)->count());
    }

    public function test_no_disponible_y_sin_reporte_son_distinguibles(): void
    {
        $colegio = $this->crearColegio($this->crearPlan());
        $arbitro = $this->crearArbitro($colegio);

        $this->disponibilidad->guardarSemana($arbitro, [
            ['fecha' => '2026-07-04', 'franja' => DisponibilidadArbitro::FRANJA_NO_DISPONIBLE],
            ['fecha' => '2026-07-05', 'franja' => null],
        ]);

        $marcoNoDisponible = DisponibilidadArbitro::where('idArbitro', $arbitro->idArbitro)
            ->where('fechaDisponibilidad', '2026-07-04')->exists();
        $sinReporte = DisponibilidadArbitro::where('idArbitro', $arbitro->idArbitro)
            ->where('fechaDisponibilidad', '2026-07-05')->exists();

        $this->assertTrue($marcoNoDisponible, 'El día que dijo "no disponible" debe tener registro.');
        $this->assertFalse($sinReporte, 'El día que nunca reportó no debe tener registro.');
    }

    public function test_no_deja_reportar_dos_veces_en_el_mismo_ciclo(): void
    {
        // Ancla la fecha "actual" (mismo día que usa el test de más abajo) para que
        // la ventana de reporte no dependa de en qué día real se corra el suite.
        Carbon::setTestNow('2026-07-03'); // viernes

        $colegio = $this->crearColegio($this->crearPlan());
        $arbitro = $this->crearArbitro($colegio);

        $this->disponibilidad->guardarSemana($arbitro, [
            ['fecha' => '2026-07-04', 'franja' => DisponibilidadArbitro::FRANJA_TODO_DIA],
        ]);

        $this->assertTrue($this->disponibilidad->yaReportoEstaSemana($arbitro));

        $this->expectException(\RuntimeException::class);
        $this->disponibilidad->guardarSemana($arbitro, [
            ['fecha' => '2026-07-05', 'franja' => DisponibilidadArbitro::FRANJA_AM],
        ]);
    }

    public function test_ya_reporto_respeta_el_dia_limite_configurado_del_colegio(): void
    {
        Carbon::setTestNow('2026-07-03'); // viernes

        $colegio = $this->crearColegio($this->crearPlan());
        ConfiguracionColegio::set($colegio->idColegio, ConfiguracionColegio::DIA_DISPONIBILIDAD, '5'); // viernes
        $arbitro = $this->crearArbitro($colegio);

        // Un registro de la semana anterior (antes del ciclo actual) no cuenta como "ya reportó".
        DisponibilidadArbitro::create([
            'idArbitro'           => $arbitro->idArbitro,
            'fechaDisponibilidad' => '2026-06-27',
            'franjaHoraria'       => DisponibilidadArbitro::FRANJA_TODO_DIA,
        ]);

        $this->assertFalse($this->disponibilidad->yaReportoEstaSemana($arbitro));

        Carbon::setTestNow();
    }
}

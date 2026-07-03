<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ConfiguracionColegio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Regresión del bug real: diasSemana() generaba llaves 0-6 (por cómo
 * array_map conservaba las llaves de range(1,7)), mientras que
 * getDiaDisponibilidad() y la validación del formulario esperan 1-7.
 * Elegir "Lunes" en el <select> mandaba value="0" y fallaba la validación
 * (min:1); cualquier otro día se guardaba corrido un día hacia atrás.
 */
class ConfiguracionColegioTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    public function test_dias_semana_usa_llaves_1_a_7_no_0_a_6(): void
    {
        $dias = ConfiguracionColegio::diasSemana();

        $this->assertSame([1, 2, 3, 4, 5, 6, 7], array_keys($dias));
        $this->assertSame('Lunes', $dias[1]);
        $this->assertSame('Viernes', $dias[5]);
        $this->assertSame('Domingo', $dias[7]);
        $this->assertArrayNotHasKey(0, $dias);
    }

    public function test_guardar_lunes_value_1_no_falla_la_validacion_min_1(): void
    {
        // El propio bug: value="0" para Lunes fallaba `min:1`. Confirma que
        // el value real que sale del array (clave 1) sí pasa esa regla.
        [$primeraClave] = array_keys(ConfiguracionColegio::diasSemana());

        $this->assertGreaterThanOrEqual(1, $primeraClave);
    }

    public function test_get_y_set_hacen_round_trip_correcto(): void
    {
        $colegio = $this->crearColegio($this->crearPlan());

        ConfiguracionColegio::set($colegio->idColegio, ConfiguracionColegio::DIA_DISPONIBILIDAD, '5');

        $this->assertSame(5, ConfiguracionColegio::getDiaDisponibilidad($colegio->idColegio));
        $this->assertSame('Viernes', ConfiguracionColegio::getNombreDia(5));
    }

    public function test_valor_invalido_guardado_directo_en_bd_cae_a_lunes_por_defecto(): void
    {
        $colegio = $this->crearColegio($this->crearPlan());

        ConfiguracionColegio::set($colegio->idColegio, ConfiguracionColegio::DIA_DISPONIBILIDAD, '99');

        $this->assertSame(1, ConfiguracionColegio::getDiaDisponibilidad($colegio->idColegio));
    }

    public function test_colegio_sin_configuracion_usa_lunes_por_defecto(): void
    {
        $colegio = $this->crearColegio($this->crearPlan());

        $this->assertSame(1, ConfiguracionColegio::getDiaDisponibilidad($colegio->idColegio));
    }
}

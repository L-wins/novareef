<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\SemanaNavegacion;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * El "día límite" configurado por el colegio (1=lunes...7=domingo) nunca es
 * editable — la ventana de reporte siempre son los 7 días DESPUÉS del último
 * límite. Nunca se puede reportar el día de HOY: si al consultar la ventana
 * actual el árbitro entra tarde, el inicio se recorta a "mañana" relativo al
 * día en que entra, nunca a días ya pasados ni al día de hoy.
 */
class SemanaNavegacionTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_reporta_a_tiempo_el_dia_limite_exacto(): void
    {
        // Hoy es viernes 3/07/2026, día límite = viernes (5).
        Carbon::setTestNow('2026-07-03');

        $semana = SemanaNavegacion::desde(null, 5);

        $this->assertSame('2026-07-04', $semana->lunes->toDateString());
        $this->assertSame('2026-07-10', $semana->domingo->toDateString());
        $this->assertCount(7, $semana->dias);
        $this->assertTrue($semana->esActual());
    }

    public function test_dia_limite_lunes_con_hoy_viernes_nunca_muestra_dias_pasados(): void
    {
        // Reproduce el bug original: día límite = lunes, hoy = viernes (4 días tarde).
        Carbon::setTestNow('2026-07-03');

        $semana = SemanaNavegacion::desde(null, 1);

        // Nunca debe incluir martes/miércoles/jueves (ya pasados) ni el viernes de hoy.
        $this->assertSame('2026-07-04', $semana->lunes->toDateString());
        $this->assertSame('2026-07-06', $semana->domingo->toDateString());
        $this->assertCount(3, $semana->dias);

        foreach ($semana->dias as $dia) {
            $this->assertTrue($dia->gte(Carbon::tomorrow()), "El día {$dia->toDateString()} no debería ser hoy ni pasado.");
        }
    }

    public function test_llegar_un_dia_tarde_recorta_la_ventana_desde_manana(): void
    {
        // Día límite = viernes. El árbitro debía reportar el 3/07 y entra el sábado 4/07.
        Carbon::setTestNow('2026-07-04');

        $semana = SemanaNavegacion::desde(null, 5);

        $this->assertSame('2026-07-05', $semana->lunes->toDateString(), 'Debe arrancar mañana (domingo), nunca hoy (sábado).');
        $this->assertSame('2026-07-10', $semana->domingo->toDateString(), 'El cierre no cambia, sigue siendo el próximo límite.');
        $this->assertCount(6, $semana->dias);
    }

    public function test_llegar_dos_dias_tarde_recorta_aun_mas(): void
    {
        Carbon::setTestNow('2026-07-05');

        $semana = SemanaNavegacion::desde(null, 5);

        $this->assertSame('2026-07-06', $semana->lunes->toDateString());
        $this->assertSame('2026-07-10', $semana->domingo->toDateString());
        $this->assertCount(5, $semana->dias);
    }

    public function test_el_siguiente_viernes_abre_ventana_completa_de_nuevo(): void
    {
        Carbon::setTestNow('2026-07-10');

        $semana = SemanaNavegacion::desde(null, 5);

        $this->assertSame('2026-07-11', $semana->lunes->toDateString());
        $this->assertSame('2026-07-17', $semana->domingo->toDateString());
        $this->assertCount(7, $semana->dias);
    }

    public function test_navegar_explicitamente_a_otra_semana_no_recorta_aunque_sea_pasada(): void
    {
        Carbon::setTestNow('2026-07-03');

        $actual  = SemanaNavegacion::desde(null, 1);
        $anterior = SemanaNavegacion::desde($actual->semanaPrev, 1);

        // La semana anterior se ve completa (7 días), sin recortar por "hoy",
        // y no se marca como la ventana vigente.
        $this->assertCount(7, $anterior->dias);
        $this->assertFalse($anterior->esActual());
    }

    public function test_recortar_a_hoy_false_siempre_muestra_la_semana_completa(): void
    {
        // Mismo caso que reproduce el bug (dia=lunes, hoy=viernes), pero para una
        // vista de solo lectura (ej. el resumen del designador) que no debe recortar.
        Carbon::setTestNow('2026-07-03');

        $semana = SemanaNavegacion::desde(null, 1, recortarAHoy: false);

        $this->assertSame('2026-06-30', $semana->lunes->toDateString());
        $this->assertSame('2026-07-06', $semana->domingo->toDateString());
        $this->assertCount(7, $semana->dias);
    }

    public function test_es_actual_es_falso_para_una_semana_navegada(): void
    {
        Carbon::setTestNow('2026-07-03');

        $siguiente = SemanaNavegacion::desde(
            SemanaNavegacion::desde(null, 5)->semanaNext,
            5,
        );

        $this->assertFalse($siguiente->esActual());
    }
}

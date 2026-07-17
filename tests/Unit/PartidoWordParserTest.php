<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Importacion\PartidoWordParser;
use PhpOffice\PhpWord\PhpWord;
use PHPUnit\Framework\TestCase;

/**
 * El .docx real de asociaciones arbitrales no es perfectamente uniforme:
 * ESTADIO/CIUDAD traen la etiqueta pegada al valor en la misma celda (no
 * separada como PARTIDO/DIA), y varias filas arrastran celdas sobrantes al
 * final (restos de una plantilla reciclada semana a semana). Este fixture
 * reproduce ambas cosas a propósito — confirmado contra un .docx real de
 * una asociación, no es una suposición sobre el formato.
 */
class PartidoWordParserTest extends TestCase
{
    private string $rutaTemp;

    protected function tearDown(): void
    {
        if (isset($this->rutaTemp) && file_exists($this->rutaTemp)) {
            unlink($this->rutaTemp);
        }
        parent::tearDown();
    }

    private function generarDocx(): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        // Bloque 1: contexto + tabla limpia, con celda sobrante al final
        // (simula el "resto de plantilla reciclada" real).
        $section->addText(
            "GRUPO 15\t\t\tSUB 15\t\t\t01 MARZO 07/08\t\t\tASOCIACION DE \t\tINTEGRANTES GRUPO",
            ['color' => 'FF0000'],
        );
        $table = $section->addTable();
        $table->addRow();
        foreach (['PARTIDO', 'SANTA FE', 'BETHEL', 'ARBITRO', '', 'ASOCAFA'] as $texto) {
            $table->addCell()->addText($texto);
        }
        $table->addRow();
        // ESTADIO y valor pegados en una sola celda, como en el documento real.
        $table->addCell()->addText('ESTADIO    CENTRO DEPORTIVO 1');
        $table->addCell()->addText('LINEA UNO');
        $table->addCell()->addText('');
        $table->addCell()->addText('ASOCAFA');
        $table->addRow();
        foreach (['DIA', 'SABADO 7 MARZO', 'HORA', '09:00', 'LINEA DOS', '', 'ASOCAFA'] as $texto) {
            $table->addCell()->addText($texto);
        }
        $table->addRow();
        $table->addCell()->addText('CIUDAD                BOGOTA');
        $table->addCell()->addText('EMERGENTE');
        $table->addCell()->addText('');
        $table->addCell()->addText('ASOCAFA');
        // Celdas sobrantes de una plantilla reciclada — no deben romper nada.
        $table->addCell()->addText('ALBINEGRO');
        $table->addCell()->addText('BOGOTA');

        $section->addTextBreak();

        // Bloque 2: fecha inválida a propósito, para probar errorFecha.
        $section->addText("GRUPO 17\t\t\tSUB 15\t\t\t01 MARZO 07/08\t\t\tASOCIACION DE", ['color' => 'FF0000']);
        $table2 = $section->addTable();
        $table2->addRow();
        foreach (['PARTIDO', 'CLUB REY', 'CATERPILLAR', 'ARBITRO', '', 'ASOCAFA'] as $texto) {
            $table2->addCell()->addText($texto);
        }
        $table2->addRow();
        $table2->addCell()->addText('ESTADIO     NICO');
        $table2->addCell()->addText('LINEA UNO');
        $table2->addCell()->addText('');
        $table2->addCell()->addText('ASOCAFA');
        $table2->addRow();
        foreach (['DIA', 'FECHA ILEGIBLE', 'HORA', '25:99', 'LINEA DOS', '', 'ASOCAFA'] as $texto) {
            $table2->addCell()->addText($texto);
        }
        $table2->addRow();
        $table2->addCell()->addText('CIUDAD                BOGOTA');
        $table2->addCell()->addText('EMERGENTE');
        $table2->addCell()->addText('');
        $table2->addCell()->addText('ASOCAFA');

        $this->rutaTemp = tempnam(sys_get_temp_dir(), 'novareef_docx_test_') . '.docx';
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($this->rutaTemp);

        return $this->rutaTemp;
    }

    public function test_parsea_grupo_categoria_y_fecha_desde_el_bloque_de_contexto(): void
    {
        $partidos = (new PartidoWordParser())->parsear($this->generarDocx(), 2026);

        $this->assertSame('GRUPO 15', $partidos[0]['grupoTexto']);
        $this->assertSame('SUB 15', $partidos[0]['categoriaTexto']);
        $this->assertSame('01 MARZO 07/08', $partidos[0]['fechaTexto']);
    }

    public function test_separa_equipo_local_y_visitante_desde_la_fila_partido(): void
    {
        $partidos = (new PartidoWordParser())->parsear($this->generarDocx(), 2026);

        $this->assertSame('SANTA FE', $partidos[0]['equipoLocal']);
        $this->assertSame('BETHEL', $partidos[0]['equipoVisitante']);
    }

    public function test_extrae_sede_y_ciudad_aunque_vengan_pegadas_a_la_etiqueta(): void
    {
        $partidos = (new PartidoWordParser())->parsear($this->generarDocx(), 2026);

        $this->assertSame('CENTRO DEPORTIVO 1', $partidos[0]['nombreSedeTexto']);
        $this->assertSame('BOGOTA', $partidos[0]['ciudadTexto']);
    }

    public function test_ignora_celdas_sobrantes_de_plantilla_reciclada(): void
    {
        // No debe lanzar excepcion ni contaminar ningun campo con "ALBINEGRO"/"BOGOTA" extra.
        $partidos = (new PartidoWordParser())->parsear($this->generarDocx(), 2026);

        $this->assertSame('BOGOTA', $partidos[0]['ciudadTexto']);
        $this->assertCount(2, $partidos);
    }

    public function test_asociacion_se_lee_de_la_tabla_no_del_contexto(): void
    {
        $partidos = (new PartidoWordParser())->parsear($this->generarDocx(), 2026);

        $this->assertSame('ASOCAFA', $partidos[0]['asociacionTexto']);
    }

    public function test_resuelve_fecha_y_hora_con_el_anio_del_torneo(): void
    {
        $partidos = (new PartidoWordParser())->parsear($this->generarDocx(), 2026);

        $this->assertSame('2026-03-07', $partidos[0]['fechaPartido']);
        $this->assertSame('09:00', $partidos[0]['horaPartido']);
        $this->assertFalse($partidos[0]['errorFecha']);
    }

    public function test_marca_error_de_fecha_cuando_no_se_puede_interpretar(): void
    {
        $partidos = (new PartidoWordParser())->parsear($this->generarDocx(), 2026);

        $this->assertTrue($partidos[1]['errorFecha']);
        $this->assertNull($partidos[1]['fechaPartido']);
        $this->assertNull($partidos[1]['horaPartido']);
    }
}

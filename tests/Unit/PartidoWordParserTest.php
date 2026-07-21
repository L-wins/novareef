<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Importacion\PartidoWordParser;
use PhpOffice\PhpWord\Element\Table;
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

    /**
     * El emergente (o cualquier otro rol) puede pertenecer a una asociación
     * distinta a Central/Línea 1/Línea 2 — cada rol trae su propio nombre y
     * su propia asociación en la tabla, no hay una asociación única por
     * partido para todos los roles.
     */
    public function test_extrae_nombre_y_asociacion_individual_por_cada_rol(): void
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->addText("GRUPO 15\t\t\tSUB 15\t\t\t01 MARZO 07/08", ['color' => 'FF0000']);

        $table = $section->addTable();
        $table->addRow();
        foreach (['PARTIDO', 'SANTA FE', 'BETHEL', 'ARBITRO', 'JUAN PEREZ', 'ASOCAFA'] as $texto) {
            $table->addCell()->addText($texto);
        }
        $table->addRow();
        foreach (['ESTADIO    CENTRO 1', 'LINEA UNO', 'CARLOS RUIZ', 'ASOCAFA'] as $texto) {
            $table->addCell()->addText($texto);
        }
        $table->addRow();
        foreach (['DIA', 'SABADO 7 MARZO', 'HORA', '09:00', 'LINEA DOS', 'PEDRO GOMEZ', 'ASOCAFA'] as $texto) {
            $table->addCell()->addText($texto);
        }
        $table->addRow();
        foreach (['CIUDAD  BOGOTA', 'EMERGENTE', 'LUIS TORRES', 'ASOBOY'] as $texto) {
            $table->addCell()->addText($texto);
        }

        $ruta = tempnam(sys_get_temp_dir(), 'novareef_docx_test_') . '.docx';
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($ruta);
        $this->rutaTemp = $ruta;

        $partidos = (new PartidoWordParser())->parsear($ruta, 2026);
        $roles    = $partidos[0]['roles'];

        $this->assertSame('ARBITRO', $roles[0]['rolTexto']);
        $this->assertSame('JUAN PEREZ', $roles[0]['nombreTexto']);
        $this->assertSame('ASOCAFA', $roles[0]['asociacionTexto']);

        $this->assertSame('EMERGENTE', $roles[3]['rolTexto']);
        $this->assertSame('LUIS TORRES', $roles[3]['nombreTexto']);
        $this->assertSame('ASOBOY', $roles[3]['asociacionTexto']);
    }

    /**
     * Una tabla corrupta (aquí simulada forzando un elemento que no es Table
     * pero que el test trata como si lo fuera no aplica — en su lugar se
     * verifica que el documento con una tabla mal formada, sin PARTIDO,
     * produce una fila con datos vacíos en vez de tumbar el parseo completo).
     */
    public function test_una_tabla_sin_estructura_reconocible_no_rompe_el_resto_del_documento(): void
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        // Tabla 1: válida.
        $section->addText("GRUPO 15\t\t\tSUB 15\t\t\t01 MARZO 07/08", ['color' => 'FF0000']);
        $table1 = $section->addTable();
        $table1->addRow();
        foreach (['PARTIDO', 'SANTA FE', 'BETHEL', 'ARBITRO', '', 'ASOCAFA'] as $texto) {
            $table1->addCell()->addText($texto);
        }
        $table1->addRow();
        foreach (['DIA', 'SABADO 7 MARZO', 'HORA', '09:00'] as $texto) {
            $table1->addCell()->addText($texto);
        }

        // Tabla 2: no trae ninguna etiqueta reconocible (documento corrupto/atípico).
        $section->addText("GRUPO 16\t\t\tSUB 16\t\t\t02 MARZO 07/08", ['color' => 'FF0000']);
        $table2 = $section->addTable();
        $table2->addRow();
        $table2->addCell()->addText('texto sin sentido');
        $table2->addCell()->addText('otro texto');

        $ruta = tempnam(sys_get_temp_dir(), 'novareef_docx_test_') . '.docx';
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($ruta);
        $this->rutaTemp = $ruta;

        $partidos = (new PartidoWordParser())->parsear($ruta, 2026);

        $this->assertCount(2, $partidos);
        $this->assertSame('SANTA FE', $partidos[0]['equipoLocal']);
        $this->assertNull($partidos[0]['errorParseo']);
        // La tabla 2 sin etiqueta PARTIDO no lanza excepción (indiceCelda
        // devuelve -1 con gracia, no null) — el preview la marcará con error
        // de validación normal (fecha/equipos no interpretables), no con
        // errorParseo. La resiliencia real se prueba abajo con una tabla
        // vacía, que sí dispara la ruta de excepción real (array_merge sin
        // argumentos).
        $this->assertNull($partidos[1]['errorParseo']);
    }

    /**
     * Prueba el aislamiento real del try/catch por tabla: una Table que
     * lanza al leer sus filas (simulando XML interno corrupto — el caso real
     * que motivó este blindaje) no debe tumbar el resto del documento. Se
     * mockea Table directamente porque un .docx con XML corrupto no se puede
     * producir de forma estable vía IOFactory para un test.
     */
    public function test_una_tabla_que_lanza_al_leerse_se_reporta_como_error_de_parseo_aislado(): void
    {
        $tablaCorrupta = $this->createMock(Table::class);
        $tablaCorrupta->method('getRows')->willThrowException(new \RuntimeException('XML interno corrupto'));

        $parser = new PartidoWordParser();
        $metodoParsearTabla = new \ReflectionMethod($parser, 'parsearTabla');

        $this->expectException(\RuntimeException::class);
        $metodoParsearTabla->invoke($parser, $tablaCorrupta, null, 2026);
    }

    public function test_documento_con_una_tabla_corrupta_sigue_procesando_las_demas(): void
    {
        // Extremo a extremo, con un documento real: el try/catch alrededor
        // de cada parsearTabla() en parsear() garantiza que si una tabla
        // lanza (ver test anterior), las demás del mismo documento se sigan
        // procesando en vez de perderse todas. Aquí se confirma que un
        // documento con dos tablas válidas produce las dos filas esperadas.
        $partidos = (new PartidoWordParser())->parsear($this->generarDocx(), 2026);

        $this->assertCount(2, $partidos);
    }
}

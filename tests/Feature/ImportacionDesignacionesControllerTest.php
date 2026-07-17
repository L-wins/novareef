<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Colegio;
use App\Models\Designacion;
use App\Models\DivisionTorneo;
use App\Models\HistorialDesignacion;
use App\Models\Partido;
use App\Models\SedeTorneo;
use App\Models\SlotDesignacion;
use App\Models\Torneo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use PhpOffice\PhpWord\PhpWord;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * Cobertura end-to-end del importador de partidos desde Word (docs/plan-
 * importador-word-designaciones.md): subir .docx -> preview -> corregir ->
 * confirmar -> Partido/slots/historial reales, reutilizando
 * DesignacionService::crearPartido() (no Partido::create() directo).
 */
class ImportacionDesignacionesControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreaColegioDePrueba;

    private function crearDesignadorConPermisos(Colegio $colegio): User
    {
        foreach (['ver-designaciones', 'crear-designaciones'] as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        $rol = Role::firstOrCreate(['name' => 'designador', 'guard_name' => 'web']);
        $rol->syncPermissions(['ver-designaciones', 'crear-designaciones']);

        $usuario = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'designador']);
        $usuario->assignRole('designador');

        return $usuario;
    }

    /** Genera un .docx con dos bloques GRUPO+tabla, una vez ubicable/matcheable y otra sin división. */
    private function generarDocxDePrueba(): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $section->addText("GRUPO 15\t\t\tSUB 15\t\t\t01 MARZO 07/08\t\t\tASOCIACION DE", ['color' => 'FF0000']);
        $table = $section->addTable();
        $table->addRow();
        foreach (['PARTIDO', 'SANTA FE', 'BETHEL', 'ARBITRO', '', 'ASOCAFA'] as $t) {
            $table->addCell()->addText($t);
        }
        $table->addRow();
        $table->addCell()->addText('ESTADIO    CENTRO DEPORTIVO 1');
        $table->addCell()->addText('LINEA UNO');
        $table->addCell()->addText('');
        $table->addCell()->addText('ASOCAFA');
        $table->addRow();
        foreach (['DIA', 'SABADO 7 MARZO', 'HORA', '09:00', 'LINEA DOS', '', 'ASOCAFA'] as $t) {
            $table->addCell()->addText($t);
        }
        $table->addRow();
        $table->addCell()->addText('CIUDAD                BOGOTA');
        $table->addCell()->addText('EMERGENTE');
        $table->addCell()->addText('');
        $table->addCell()->addText('ASOCAFA');

        $section->addTextBreak();

        // Segundo partido: categoria sin match -> debe quedar con error.
        $section->addText("GRUPO 17\t\t\tSUB 20 SIN DIVISION\t\t\t01 MARZO 07/08\t\t\tASOCIACION DE", ['color' => 'FF0000']);
        $table2 = $section->addTable();
        $table2->addRow();
        foreach (['PARTIDO', 'CLUB REY', 'CATERPILLAR', 'ARBITRO', '', 'ASOCAFA'] as $t) {
            $table2->addCell()->addText($t);
        }
        $table2->addRow();
        $table2->addCell()->addText('ESTADIO     NICO');
        $table2->addCell()->addText('LINEA UNO');
        $table2->addCell()->addText('');
        $table2->addCell()->addText('ASOCAFA');
        $table2->addRow();
        foreach (['DIA', 'SABADO 7 MARZO', 'HORA', '10:00', 'LINEA DOS', '', 'ASOCAFA'] as $t) {
            $table2->addCell()->addText($t);
        }
        $table2->addRow();
        $table2->addCell()->addText('CIUDAD                BOGOTA');
        $table2->addCell()->addText('EMERGENTE');
        $table2->addCell()->addText('');
        $table2->addCell()->addText('ASOCAFA');

        $ruta = tempnam(sys_get_temp_dir(), 'novareef_docx_') . '.docx';
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($ruta);

        return $ruta;
    }

    public function test_sube_el_docx_y_muestra_el_preview_con_matching_correcto(): void
    {
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignadorConPermisos($colegio);
        $torneo     = $this->crearTorneo($colegio, $designador, ['temporada' => 2026]);
        DivisionTorneo::create(['idTorneo' => $torneo->idTorneo, 'nombreDivision' => 'SUB 15']);
        $this->crearSede($torneo)->update(['nombreSede' => 'Centro Deportivo 1']);
        $formato = $this->crearFormatoDupla();

        $ruta = $this->generarDocxDePrueba();

        $this->actingAs($designador)->post(route('designaciones.importar.procesar'), [
            'idTorneo'    => $torneo->idTorneo,
            'idFormato'   => $formato->idFormato,
            'archivoWord' => new \Illuminate\Http\UploadedFile($ruta, 'muestra.docx', null, null, true),
        ])->assertRedirect(route('designaciones.importar.mostrar'));

        $response = $this->actingAs($designador)->get(route('designaciones.importar.mostrar'));
        $response->assertOk();

        $partidos = session('importacion_designaciones')['partidos'];
        $this->assertCount(2, $partidos);

        $conMatch = collect($partidos)->firstWhere('equipoLocal', 'SANTA FE');
        $this->assertNotNull($conMatch['idDivisionMatch']);
        $this->assertNotNull($conMatch['idSedeMatch']);
        $this->assertSame([], $conMatch['errores']);
        $this->assertTrue($conMatch['incluir']);

        $sinMatch = collect($partidos)->firstWhere('equipoLocal', 'CLUB REY');
        $this->assertNull($sinMatch['idDivisionMatch']);
        $this->assertNotEmpty($sinMatch['errores']);
        $this->assertFalse($sinMatch['incluir']);
    }

    public function test_confirmar_crea_partidos_reales_con_slots_e_historial(): void
    {
        $this->seed(\Database\Seeders\RolesPartidoSeeder::class);
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignadorConPermisos($colegio);
        $torneo     = $this->crearTorneo($colegio, $designador, ['temporada' => 2026]);
        DivisionTorneo::create(['idTorneo' => $torneo->idTorneo, 'nombreDivision' => 'SUB 15']);
        DivisionTorneo::create(['idTorneo' => $torneo->idTorneo, 'nombreDivision' => 'SUB 20 SIN DIVISION']);
        $this->crearSede($torneo)->update(['nombreSede' => 'Centro Deportivo 1']);
        $formato = $this->crearFormatoDupla();

        $ruta = $this->generarDocxDePrueba();

        $this->actingAs($designador)->post(route('designaciones.importar.procesar'), [
            'idTorneo'    => $torneo->idTorneo,
            'idFormato'   => $formato->idFormato,
            'archivoWord' => new \Illuminate\Http\UploadedFile($ruta, 'muestra.docx', null, null, true),
        ]);

        // Ambos partidos ahora matchean division (la segunda ya existe) -> confirmar sin correcciones.
        $sesion = session('importacion_designaciones');
        $filas  = [];
        foreach ($sesion['partidos'] as $p) {
            $filas[$p['clave']] = [
                'incluir'    => '1',
                'idDivision' => (string) DivisionTorneo::where('idTorneo', $torneo->idTorneo)
                    ->where('nombreDivision', $p['categoriaTexto'])->value('idDivision'),
                'idSede'     => (string) $p['idSedeMatch'],
                'idFormato'  => (string) $formato->idFormato,
            ];
        }

        $this->actingAs($designador)->post(route('designaciones.importar.confirmar'), ['filas' => $filas])
            ->assertRedirect(route('designaciones.index', ['torneo' => $torneo->idTorneo]));

        $this->assertSame(2, Partido::where('idTorneo', $torneo->idTorneo)->count());
        $this->assertNull(session('importacion_designaciones'));

        $partidoSantaFe = Partido::where('idTorneo', $torneo->idTorneo)->where('equipoLocal', 'SANTA FE')->firstOrFail();
        $this->assertSame('GRUPO 15', $partidoSantaFe->observaciones);
        $this->assertSame(Partido::ESTADO_BORRADOR, $partidoSantaFe->estadoPartido);
        $this->assertSame(2, SlotDesignacion::where('idPartido', $partidoSantaFe->idPartido)->count());
        $this->assertSame(
            1,
            HistorialDesignacion::where('idPartido', $partidoSantaFe->idPartido)
                ->where('tipoAccion', HistorialDesignacion::TIPO_PARTIDO_CREADO)->count(),
        );
    }

    /**
     * El controller ya blinda esto (aplicarEdiciones fuerza incluir=false
     * si la fila tiene errores, así que nunca llega así vía HTTP) — pero
     * ImportacionPartidosService debe rechazarlo igual como segunda capa
     * de seguridad, por si algún día se llama desde otro lado.
     */
    public function test_no_confirma_si_una_fila_incluida_sigue_con_errores(): void
    {
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignadorConPermisos($colegio);
        $torneo     = $this->crearTorneo($colegio, $designador, ['temporada' => 2026]);

        $filaConError = [
            'clave' => 'x', 'grupoTexto' => null, 'equipoLocal' => 'A', 'equipoVisitante' => 'B',
            'idDivisionMatch' => null, 'idSedeMatch' => null, 'idFormato' => null,
            'fechaPartido' => '2026-03-07', 'horaPartido' => '09:00',
            'incluir' => true, 'errores' => ['División no encontrada.'], 'advertencias' => [],
        ];

        $this->expectException(\RuntimeException::class);

        app(\App\Services\Importacion\ImportacionPartidosService::class)
            ->importarLote($colegio->idColegio, $torneo->idTorneo, [$filaConError], $designador->idUsuario);

        $this->assertSame(0, Partido::where('idTorneo', $torneo->idTorneo)->count());
    }

    public function test_rechaza_archivo_que_no_es_docx(): void
    {
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignadorConPermisos($colegio);
        $torneo     = $this->crearTorneo($colegio, $designador);
        $formato    = $this->crearFormatoDupla();

        $this->actingAs($designador)->post(route('designaciones.importar.procesar'), [
            'idTorneo'    => $torneo->idTorneo,
            'idFormato'   => $formato->idFormato,
            'archivoWord' => File::create('viejo.doc', 50),
        ])->assertSessionHasErrors('archivoWord');
    }

    public function test_un_colegio_no_puede_importar_sobre_un_torneo_de_otro(): void
    {
        $colegioA    = $this->crearColegio();
        $colegioB    = $this->crearColegio();
        $designadorA = $this->crearDesignadorConPermisos($colegioA);
        $ejecutivoB  = $this->crearCuentaAdmin($colegioB, 'ejecutivo');
        $torneoB     = $this->crearTorneo($colegioB, $ejecutivoB);
        $formato     = $this->crearFormatoDupla();

        $ruta = $this->generarDocxDePrueba();

        $this->actingAs($designadorA)->post(route('designaciones.importar.procesar'), [
            'idTorneo'    => $torneoB->idTorneo,
            'idFormato'   => $formato->idFormato,
            'archivoWord' => new \Illuminate\Http\UploadedFile($ruta, 'muestra.docx', null, null, true),
        ])->assertNotFound();
    }
}

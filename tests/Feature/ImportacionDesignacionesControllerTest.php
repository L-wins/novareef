<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Colegio;
use App\Models\DivisionTorneo;
use App\Models\HistorialDesignacion;
use App\Models\ImportacionPartidoFila;
use App\Models\ImportacionPartidos;
use App\Models\Partido;
use App\Models\SlotDesignacion;
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
 * importador-word-designaciones.md): subir .docx -> preview (persistido en
 * BD, no sesión) -> corregir -> confirmar -> Partido/slots/historial reales,
 * reutilizando DesignacionService::crearPartido() (no Partido::create()
 * directo). Incluye matching de árbitros por rol y reversión.
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

    /**
     * Genera un .docx con dos bloques GRUPO+tabla, uno ubicable/matcheable
     * (incluyendo un árbitro real en la fila ARBITRO cuando $nombreArbitro
     * se pasa) y otro sin división.
     */
    private function generarDocxDePrueba(?string $nombreArbitro = null): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $section->addText("GRUPO 15\t\t\tSUB 15\t\t\t01 MARZO 07/08\t\t\tASOCIACION DE", ['color' => 'FF0000']);
        $table = $section->addTable();
        $table->addRow();
        foreach (['PARTIDO', 'SANTA FE', 'BETHEL', 'ARBITRO', $nombreArbitro ?? '', 'ASOCAFA'] as $t) {
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

    public function test_sube_el_docx_y_persiste_el_preview_en_bd_con_matching_correcto(): void
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

        $importacion = ImportacionPartidos::where('idColegio', $colegio->idColegio)->sole();
        $this->assertSame(ImportacionPartidos::ESTADO_PROCESANDO, $importacion->estado);
        $this->assertSame('muestra.docx', $importacion->nombreArchivoOriginal);
        $this->assertSame(2, $importacion->filas()->count());

        $response = $this->actingAs($designador)->get(route('designaciones.importar.mostrar'));
        $response->assertOk();

        $conMatch = $importacion->filas()->where('equipoLocal', 'SANTA FE')->sole();
        $this->assertNotNull($conMatch->idDivisionMatch);
        $this->assertNotNull($conMatch->idSedeMatch);
        $this->assertSame([], $conMatch->errores);
        $this->assertTrue($conMatch->incluir);

        $sinMatch = $importacion->filas()->where('equipoLocal', 'CLUB REY')->sole();
        $this->assertNull($sinMatch->idDivisionMatch);
        $this->assertNotEmpty($sinMatch->errores);
        $this->assertFalse($sinMatch->incluir);
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

        $importacion = ImportacionPartidos::where('idColegio', $colegio->idColegio)->sole();

        // Ambos partidos ahora matchean división (la segunda ya existe) -> confirmar sin correcciones.
        $filas = [];
        foreach ($importacion->filas as $f) {
            $filas[$f->clave] = [
                'incluir'    => '1',
                'idDivision' => (string) DivisionTorneo::where('idTorneo', $torneo->idTorneo)
                    ->where('nombreDivision', $f->categoriaTexto)->value('idDivision'),
                'idSede'     => (string) $f->idSedeMatch,
                'idFormato'  => (string) $formato->idFormato,
            ];
        }

        $this->actingAs($designador)->post(route('designaciones.importar.confirmar'), ['filas' => $filas])
            ->assertRedirect(route('designaciones.index', ['torneo' => $torneo->idTorneo]));

        $this->assertSame(2, Partido::where('idTorneo', $torneo->idTorneo)->count());

        $importacion->refresh();
        $this->assertSame(ImportacionPartidos::ESTADO_CONFIRMADA, $importacion->estado);
        $this->assertSame(2, $importacion->totalCreados);

        $partidoSantaFe = Partido::where('idTorneo', $torneo->idTorneo)->where('equipoLocal', 'SANTA FE')->firstOrFail();
        $this->assertSame('GRUPO 15', $partidoSantaFe->observaciones);
        $this->assertSame(Partido::ESTADO_BORRADOR, $partidoSantaFe->estadoPartido);
        $this->assertSame($importacion->idImportacion, $partidoSantaFe->idImportacion);
        $this->assertSame(2, SlotDesignacion::where('idPartido', $partidoSantaFe->idPartido)->count());
        $this->assertSame(
            1,
            HistorialDesignacion::where('idPartido', $partidoSantaFe->idPartido)
                ->where('tipoAccion', HistorialDesignacion::TIPO_PARTIDO_CREADO)->count(),
        );
    }

    /**
     * El emergente (o cualquier otro rol) puede pertenecer a otra asociación
     * — cuando el nombre del Word SÍ matchea a un árbitro real del colegio
     * que importa, la designación se crea automáticamente al confirmar.
     */
    public function test_confirmar_designa_automaticamente_al_arbitro_que_matchea_por_nombre(): void
    {
        $this->seed(\Database\Seeders\RolesPartidoSeeder::class);
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignadorConPermisos($colegio);
        $torneo     = $this->crearTorneo($colegio, $designador, ['temporada' => 2026]);
        DivisionTorneo::create(['idTorneo' => $torneo->idTorneo, 'nombreDivision' => 'SUB 15']);
        DivisionTorneo::create(['idTorneo' => $torneo->idTorneo, 'nombreDivision' => 'SUB 20 SIN DIVISION']);
        $this->crearSede($torneo)->update(['nombreSede' => 'Centro Deportivo 1']);
        $formato = $this->crearFormatoDupla();

        $arbitroCentral = $this->crearArbitro($colegio, ['usuario' => ['nombreUsuario' => 'Juan Pérez']]);

        $ruta = $this->generarDocxDePrueba('Juan Pérez');

        $this->actingAs($designador)->post(route('designaciones.importar.procesar'), [
            'idTorneo'    => $torneo->idTorneo,
            'idFormato'   => $formato->idFormato,
            'archivoWord' => new \Illuminate\Http\UploadedFile($ruta, 'muestra.docx', null, null, true),
        ]);

        $importacion = ImportacionPartidos::where('idColegio', $colegio->idColegio)->sole();

        $filaSantaFe = $importacion->filas()->where('equipoLocal', 'SANTA FE')->sole();
        $rolArbitro  = collect($filaSantaFe->designacionesMatch)->firstWhere('rolTexto', 'ARBITRO');
        $this->assertNotNull($rolArbitro, 'El parser debe extraer la fila del rol ARBITRO.');
        $this->assertSame($arbitroCentral->idArbitro, $rolArbitro['idArbitroMatch']);

        $filas = [];
        foreach ($importacion->filas as $f) {
            $filas[$f->clave] = [
                'incluir'    => '1',
                'idDivision' => (string) DivisionTorneo::where('idTorneo', $torneo->idTorneo)
                    ->where('nombreDivision', $f->categoriaTexto)->value('idDivision'),
                'idSede'     => (string) $f->idSedeMatch,
                'idFormato'  => (string) $formato->idFormato,
            ];
        }

        $this->actingAs($designador)->post(route('designaciones.importar.confirmar'), ['filas' => $filas]);

        $partidoSantaFe = Partido::where('idTorneo', $torneo->idTorneo)->where('equipoLocal', 'SANTA FE')->firstOrFail();
        $this->assertTrue(
            $partidoSantaFe->designaciones()->where('idArbitro', $arbitroCentral->idArbitro)->exists(),
            'El árbitro que matcheó por nombre debe quedar designado automáticamente.',
        );
    }

    public function test_marca_fila_como_posible_duplicado_si_ya_existe_un_partido_igual(): void
    {
        $this->seed(\Database\Seeders\RolesPartidoSeeder::class);
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignadorConPermisos($colegio);
        $torneo     = $this->crearTorneo($colegio, $designador, ['temporada' => 2026]);
        $division   = DivisionTorneo::create(['idTorneo' => $torneo->idTorneo, 'nombreDivision' => 'SUB 15']);
        $sede       = $this->crearSede($torneo);
        $sede->update(['nombreSede' => 'Centro Deportivo 1']);
        $formato = $this->crearFormatoDupla();

        // Partido ya existente idéntico al que trae el Word.
        app(\App\Services\DesignacionService::class)->crearPartido($colegio->idColegio, [
            'idTorneo' => $torneo->idTorneo, 'idDivision' => $division->idDivision,
            'idSede' => $sede->idSede, 'idFormato' => $formato->idFormato,
            'equipoLocal' => 'SANTA FE', 'equipoVisitante' => 'BETHEL',
            'fechaPartido' => '2026-03-07', 'horaPartido' => '09:00', 'observaciones' => null,
        ], $designador->idUsuario);

        $ruta = $this->generarDocxDePrueba();

        $this->actingAs($designador)->post(route('designaciones.importar.procesar'), [
            'idTorneo'    => $torneo->idTorneo,
            'idFormato'   => $formato->idFormato,
            'archivoWord' => new \Illuminate\Http\UploadedFile($ruta, 'muestra.docx', null, null, true),
        ]);

        $importacion = ImportacionPartidos::where('idColegio', $colegio->idColegio)->sole();
        $filaSantaFe = $importacion->filas()->where('equipoLocal', 'SANTA FE')->sole();

        $this->assertTrue($filaSantaFe->esPosibleDuplicado);
        $this->assertNotEmpty(array_filter(
            $filaSantaFe->advertencias,
            fn ($a) => str_contains($a, 'duplicado'),
        ));
    }

    /**
     * ImportacionPartidosService::confirmar() debe rechazar una fila
     * incluida con errores sin resolver como segunda capa de seguridad
     * (el controller ya fuerza incluir=false en aplicarEdiciones()).
     */
    public function test_no_confirma_si_una_fila_incluida_sigue_con_errores(): void
    {
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignadorConPermisos($colegio);
        $torneo     = $this->crearTorneo($colegio, $designador, ['temporada' => 2026]);
        $formato    = $this->crearFormatoDupla();

        $importacion = ImportacionPartidos::create([
            'idColegio' => $colegio->idColegio, 'idTorneo' => $torneo->idTorneo,
            'idUsuario' => $designador->idUsuario, 'nombreArchivoOriginal' => 'test.docx',
            'idFormatoDefault' => $formato->idFormato, 'estado' => ImportacionPartidos::ESTADO_PROCESANDO,
        ]);

        ImportacionPartidoFila::create([
            'idImportacion' => $importacion->idImportacion, 'clave' => 'x',
            'equipoLocal' => 'A', 'equipoVisitante' => 'B',
            'fechaPartido' => '2026-03-07', 'horaPartido' => '09:00',
            'incluir' => true, 'errores' => ['División no encontrada.'], 'advertencias' => [],
        ]);

        $this->expectException(\RuntimeException::class);

        app(\App\Services\Importacion\ImportacionPartidosService::class)
            ->confirmar($importacion, $designador->idUsuario);

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

    public function test_revertir_una_importacion_confirmada_elimina_los_partidos_que_creo(): void
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
            'idTorneo' => $torneo->idTorneo, 'idFormato' => $formato->idFormato,
            'archivoWord' => new \Illuminate\Http\UploadedFile($ruta, 'muestra.docx', null, null, true),
        ]);

        $importacion = ImportacionPartidos::where('idColegio', $colegio->idColegio)->sole();
        $filas = [];
        foreach ($importacion->filas as $f) {
            $filas[$f->clave] = [
                'incluir'    => '1',
                'idDivision' => (string) DivisionTorneo::where('idTorneo', $torneo->idTorneo)
                    ->where('nombreDivision', $f->categoriaTexto)->value('idDivision'),
                'idSede'     => (string) $f->idSedeMatch,
                'idFormato'  => (string) $formato->idFormato,
            ];
        }
        $this->actingAs($designador)->post(route('designaciones.importar.confirmar'), ['filas' => $filas]);

        $this->assertSame(2, Partido::where('idTorneo', $torneo->idTorneo)->count());

        $this->actingAs($designador)
            ->put(route('designaciones.importar.revertir', $importacion->idImportacion))
            ->assertRedirect();

        $this->assertSame(0, Partido::where('idTorneo', $torneo->idTorneo)->count());
        $this->assertSame(ImportacionPartidos::ESTADO_REVERTIDA, $importacion->fresh()->estado);
    }

    public function test_no_se_puede_revertir_un_partido_ya_publicado(): void
    {
        $this->seed(\Database\Seeders\RolesPartidoSeeder::class);
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignadorConPermisos($colegio);
        $torneo     = $this->crearTorneo($colegio, $designador, ['temporada' => 2026]);
        $division   = DivisionTorneo::create(['idTorneo' => $torneo->idTorneo, 'nombreDivision' => 'SUB 15']);
        $sede       = $this->crearSede($torneo);
        $formato    = $this->crearFormatoDupla();

        $importacion = ImportacionPartidos::create([
            'idColegio' => $colegio->idColegio, 'idTorneo' => $torneo->idTorneo,
            'idUsuario' => $designador->idUsuario, 'nombreArchivoOriginal' => 'test.docx',
            'idFormatoDefault' => $formato->idFormato, 'estado' => ImportacionPartidos::ESTADO_CONFIRMADA,
            'totalCreados' => 1, 'confirmadaEn' => now(),
        ]);

        $arbitroCentral   = $this->crearArbitro($colegio);
        $arbitroAsistente = $this->crearArbitro($colegio);
        $servicio = app(\App\Services\DesignacionService::class);

        $partido = $servicio->crearPartido($colegio->idColegio, [
            'idTorneo' => $torneo->idTorneo, 'idDivision' => $division->idDivision,
            'idSede' => $sede->idSede, 'idFormato' => $formato->idFormato, 'idImportacion' => $importacion->idImportacion,
            'equipoLocal' => 'SANTA FE', 'equipoVisitante' => 'BETHEL',
            'fechaPartido' => '2026-03-07', 'horaPartido' => '09:00', 'observaciones' => null,
        ], $designador->idUsuario);

        $servicio->asignarArbitro($partido, $arbitroCentral->idArbitro, $this->idRolPorNombre('Central'), $colegio->idColegio, $designador->idUsuario);
        $servicio->asignarArbitro($partido, $arbitroAsistente->idArbitro, $this->idRolPorNombre('Asistente'), $colegio->idColegio, $designador->idUsuario);
        $servicio->publicarPartido($partido->fresh('formato'), $designador);

        $this->actingAs($designador)
            ->put(route('designaciones.importar.revertir', $importacion->idImportacion))
            ->assertRedirect();

        $this->assertSame(1, Partido::where('idTorneo', $torneo->idTorneo)->count());
        $this->assertSame(ImportacionPartidos::ESTADO_CONFIRMADA, $importacion->fresh()->estado);
    }
}

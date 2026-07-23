<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Arbitro;
use App\Models\CategoriaArbitro;
use App\Models\Colegio;
use App\Models\DocumentoArbitro;
use App\Models\RequisitoDocumentoArbitro;
use App\Models\User;
use App\Services\DocumentoArbitroService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

class DocumentosArbitroControllerTest extends TestCase
{
    use CreaColegioDePrueba;
    use RefreshDatabase;

    private function crearEjecutivoConPermisos(Colegio $colegio): User
    {
        foreach (['ver-arbitros', 'crear-arbitros', 'editar-arbitros'] as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        $rol = Role::firstOrCreate(['name' => 'ejecutivo', 'guard_name' => 'web']);
        $rol->syncPermissions(['ver-arbitros', 'crear-arbitros', 'editar-arbitros']);
        Role::firstOrCreate(['name' => 'arbitro', 'guard_name' => 'web']);

        $usuario = User::factory()->create([
            'idColegio' => $colegio->idColegio,
            'rolUsuario' => 'ejecutivo',
        ]);
        $usuario->assignRole('ejecutivo');

        return $usuario;
    }

    private function crearTesoreroConPermisos(Colegio $colegio): User
    {
        Permission::firstOrCreate(['name' => 'ver-arbitros', 'guard_name' => 'web']);

        $rol = Role::firstOrCreate(['name' => 'tesorero', 'guard_name' => 'web']);
        $rol->syncPermissions(['ver-arbitros']);

        $usuario = User::factory()->create([
            'idColegio' => $colegio->idColegio,
            'rolUsuario' => 'tesorero',
        ]);
        $usuario->assignRole('tesorero');

        return $usuario;
    }

    private function crearRequisito(Arbitro $arbitro, array $overrides = []): RequisitoDocumentoArbitro
    {
        return RequisitoDocumentoArbitro::create(array_merge([
            'idColegio' => $arbitro->idColegio,
            'nombre' => 'Hoja de vida',
            'descripcion' => 'Adjunta el formato actualizado.',
            'obligatorio' => true,
            'requiereRevision' => true,
            'activo' => true,
            'orden' => 1,
        ], $overrides));
    }

    public function test_el_colegio_configura_requisito_documental_con_plantilla(): void
    {
        Storage::fake('local');

        $colegio = $this->crearColegio();
        $ejecutivo = $this->crearEjecutivoConPermisos($colegio);
        $categoria = CategoriaArbitro::where('idColegio', $colegio->idColegio)->firstOrFail();

        $archivo = UploadedFile::fake()->create('formato-hoja-vida.docx', 120, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $this->actingAs($ejecutivo)->post(route('requisitos-documentos-arbitro.store'), [
            'nombre' => 'Hoja de vida',
            'descripcion' => 'Diligencia la plantilla y vuelve a cargarla.',
            'alcanceRequisito' => 'categoria:'.$categoria->idCategoria,
            'orden' => 1,
            'obligatorio' => '1',
            'requiereRevision' => '1',
            'activo' => '1',
            'plantilla' => $archivo,
        ])->assertRedirect();

        $requisito = RequisitoDocumentoArbitro::firstOrFail();

        $this->assertSame($colegio->idColegio, $requisito->idColegio);
        $this->assertSame($categoria->idCategoria, $requisito->idCategoria);
        $this->assertTrue($requisito->obligatorio);
        $this->assertTrue($requisito->requiereRevision);
        Storage::disk('local')->assertExists($requisito->plantillaRuta);

        $this->actingAs($ejecutivo)->get(route('requisitos-documentos-arbitro.index'))
            ->assertOk()
            ->assertSee('Hoja de vida')
            ->assertSee($categoria->nombreCategoria);
    }

    public function test_crear_y_pausar_requisito_por_ajax_devuelve_json_con_la_lista_actualizada(): void
    {
        $colegio = $this->crearColegio();
        $ejecutivo = $this->crearEjecutivoConPermisos($colegio);

        $respuestaStore = $this->actingAs($ejecutivo)->post(route('requisitos-documentos-arbitro.store'), [
            'nombre' => 'Certificado médico',
            'orden' => 1,
            'obligatorio' => '1',
            'requiereRevision' => '1',
            'activo' => '1',
        ], ['X-Requested-With' => 'XMLHttpRequest']);

        $respuestaStore->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'message', 'regions' => ['requisitos']]);

        $requisito = RequisitoDocumentoArbitro::where('nombre', 'Certificado médico')->firstOrFail();
        $this->assertStringContainsString('Certificado médico', $respuestaStore->json('regions.requisitos'));

        $respuestaEstado = $this->actingAs($ejecutivo)
            ->put(route('requisitos-documentos-arbitro.estado', $requisito->idRequisito), [], ['X-Requested-With' => 'XMLHttpRequest']);

        $respuestaEstado->assertOk()->assertJson(['success' => true, 'message' => 'Requisito documental pausado.']);
        $this->assertFalse($requisito->fresh()->activo);
    }

    public function test_elimina_un_requisito_sin_entregas(): void
    {
        $colegio = $this->crearColegio();
        $ejecutivo = $this->crearEjecutivoConPermisos($colegio);
        $arbitro = $this->crearArbitro($colegio);
        $requisito = $this->crearRequisito($arbitro);

        $respuesta = $this->actingAs($ejecutivo)
            ->delete(route('requisitos-documentos-arbitro.destroy', $requisito->idRequisito), [], ['X-Requested-With' => 'XMLHttpRequest']);

        $respuesta->assertOk()->assertJson(['success' => true, 'message' => 'Requisito documental eliminado.']);
        $this->assertDatabaseMissing('requisitos_documento_arbitro', ['idRequisito' => $requisito->idRequisito]);
    }

    public function test_no_elimina_un_requisito_con_entregas(): void
    {
        Storage::fake('local');

        $colegio = $this->crearColegio();
        $ejecutivo = $this->crearEjecutivoConPermisos($colegio);
        $arbitro = $this->crearArbitro($colegio);
        $requisito = $this->crearRequisito($arbitro);

        app(DocumentoArbitroService::class)->guardarEntrega(
            $arbitro,
            $requisito,
            UploadedFile::fake()->create('hoja-vida.pdf', 100, 'application/pdf'),
        );

        $respuesta = $this->actingAs($ejecutivo)->delete(route('requisitos-documentos-arbitro.destroy', $requisito->idRequisito));

        $respuesta->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('requisitos_documento_arbitro', ['idRequisito' => $requisito->idRequisito]);
    }

    public function test_eliminar_requisito_borra_la_plantilla_del_disco(): void
    {
        Storage::fake('local');

        $colegio = $this->crearColegio();
        $ejecutivo = $this->crearEjecutivoConPermisos($colegio);
        $arbitro = $this->crearArbitro($colegio);
        $requisito = $this->crearRequisito($arbitro);

        app(DocumentoArbitroService::class)->guardarPlantilla(
            $requisito,
            UploadedFile::fake()->create('plantilla.pdf', 100, 'application/pdf'),
        );

        $ruta = $requisito->fresh()->plantillaRuta;
        Storage::disk('local')->assertExists($ruta);

        $this->actingAs($ejecutivo)->delete(route('requisitos-documentos-arbitro.destroy', $requisito->idRequisito))
            ->assertRedirect();

        Storage::disk('local')->assertMissing($ruta);
    }

    public function test_el_arbitro_entrega_documento_y_no_bloquea_su_estado_operativo(): void
    {
        Storage::fake('local');

        $colegio = $this->crearColegio();
        $arbitro = $this->crearArbitro($colegio, ['arbitro' => ['estadoArbitro' => 'activo']]);
        $requisito = $this->crearRequisito($arbitro);

        $archivo = UploadedFile::fake()->create('hoja-vida.pdf', 300, 'application/pdf');

        $this->actingAs($arbitro->usuario)->post(route('documentos.arbitro.store', [
            $arbitro->idArbitro,
            $requisito->idRequisito,
        ]), [
            'archivo' => $archivo,
        ])->assertRedirect();

        $documento = DocumentoArbitro::firstOrFail();

        $this->assertSame(DocumentoArbitro::ESTADO_EN_REVISION, $documento->estadoRevision);
        $this->assertSame(1, $documento->version);
        $this->assertSame('activo', $arbitro->fresh()->estadoArbitro);
        Storage::disk('local')->assertExists($documento->archivoRuta);

        $this->actingAs($arbitro->usuario)->get(route('arbitros.mi-perfil'))
            ->assertOk()
            ->assertViewHas('documentosResumen', fn (array $resumen): bool => $resumen['pendientesRevision'] === 1);
    }

    public function test_la_entrega_por_ajax_devuelve_json_con_la_region_actualizada(): void
    {
        Storage::fake('local');

        $colegio = $this->crearColegio();
        $arbitro = $this->crearArbitro($colegio);
        $requisito = $this->crearRequisito($arbitro);

        $respuesta = $this->actingAs($arbitro->usuario)->post(route('documentos.arbitro.store', [
            $arbitro->idArbitro,
            $requisito->idRequisito,
        ]), [
            'archivo' => UploadedFile::fake()->create('hoja-vida.pdf', 200, 'application/pdf'),
        ], ['X-Requested-With' => 'XMLHttpRequest']);

        $respuesta->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'message', 'regions' => ['documentos']]);

        $this->assertStringContainsString($requisito->nombre, $respuesta->json('regions.documentos'));
    }

    public function test_los_requisitos_pueden_aplicar_solo_a_una_categoria(): void
    {
        Storage::fake('local');

        $colegio = $this->crearColegio();
        $arbitro = $this->crearArbitro($colegio);
        $otraCategoria = CategoriaArbitro::create([
            'idColegio' => $colegio->idColegio,
            'nombreCategoria' => 'Nacional B',
            'activa' => true,
            'esPorDefecto' => false,
        ]);

        $global = $this->crearRequisito($arbitro, ['nombre' => 'Documento global']);
        $propio = $this->crearRequisito($arbitro, [
            'nombre' => 'Documento de mi categoria',
            'idCategoria' => $arbitro->idCategoria,
        ]);
        $ajeno = $this->crearRequisito($arbitro, [
            'nombre' => 'Documento de otra categoria',
            'idCategoria' => $otraCategoria->idCategoria,
        ]);

        $this->actingAs($arbitro->usuario)->get(route('arbitros.mi-perfil'))
            ->assertOk()
            ->assertSee($global->nombre)
            ->assertSee($propio->nombre)
            ->assertDontSee($ajeno->nombre)
            ->assertViewHas('documentosResumen', fn (array $resumen): bool => $resumen['total'] === 2);

        $this->actingAs($arbitro->usuario)->post(route('documentos.arbitro.store', [
            $arbitro->idArbitro,
            $ajeno->idRequisito,
        ]), [
            'archivo' => UploadedFile::fake()->create('no-aplica.pdf', 100, 'application/pdf'),
        ])->assertSessionHas('error');

        $this->assertDatabaseMissing('documentos_arbitro', [
            'idArbitro' => $arbitro->idArbitro,
            'idRequisito' => $ajeno->idRequisito,
        ]);
    }

    public function test_los_requisitos_pueden_aplicar_solo_a_un_arbitro(): void
    {
        Storage::fake('local');

        $colegio = $this->crearColegio();
        $arbitro = $this->crearArbitro($colegio);
        $otroArbitro = $this->crearArbitro($colegio);

        $global = $this->crearRequisito($arbitro, ['nombre' => 'Documento global']);
        $propio = $this->crearRequisito($arbitro, [
            'nombre' => 'Documento solo para mi',
            'idArbitro' => $arbitro->idArbitro,
        ]);
        $ajeno = $this->crearRequisito($arbitro, [
            'nombre' => 'Documento de otro arbitro',
            'idArbitro' => $otroArbitro->idArbitro,
        ]);

        $this->actingAs($arbitro->usuario)->get(route('arbitros.mi-perfil'))
            ->assertOk()
            ->assertSee($global->nombre)
            ->assertSee($propio->nombre)
            ->assertDontSee($ajeno->nombre)
            ->assertViewHas('documentosResumen', fn (array $resumen): bool => $resumen['total'] === 2);

        $this->actingAs($arbitro->usuario)->post(route('documentos.arbitro.store', [
            $arbitro->idArbitro,
            $ajeno->idRequisito,
        ]), [
            'archivo' => UploadedFile::fake()->create('no-aplica.pdf', 100, 'application/pdf'),
        ])->assertSessionHas('error');

        $this->assertDatabaseMissing('documentos_arbitro', [
            'idArbitro' => $arbitro->idArbitro,
            'idRequisito' => $ajeno->idRequisito,
        ]);
    }

    public function test_crear_requisito_para_un_solo_arbitro_por_ajax(): void
    {
        $colegio = $this->crearColegio();
        $ejecutivo = $this->crearEjecutivoConPermisos($colegio);
        $arbitro = $this->crearArbitro($colegio);

        $respuesta = $this->actingAs($ejecutivo)->post(route('requisitos-documentos-arbitro.store'), [
            'nombre' => 'Autorización especial',
            'alcanceRequisito' => 'arbitro:'.$arbitro->idArbitro,
            'orden' => 1,
            'obligatorio' => '1',
            'requiereRevision' => '1',
            'activo' => '1',
        ], ['X-Requested-With' => 'XMLHttpRequest']);

        $respuesta->assertOk()->assertJson(['success' => true]);

        $requisito = RequisitoDocumentoArbitro::where('nombre', 'Autorización especial')->firstOrFail();
        $this->assertSame($arbitro->idArbitro, $requisito->idArbitro);
        $this->assertNull($requisito->idCategoria);
        $this->assertStringContainsString($arbitro->usuario->nombreUsuario, $respuesta->json('regions.requisitos'));
    }

    public function test_el_select_de_arbitro_especifico_ordena_nombres_con_tilde_correctamente(): void
    {
        $colegio = $this->crearColegio();
        $ejecutivo = $this->crearEjecutivoConPermisos($colegio);

        // Collection::sortBy() compara strings byte a byte: sin normalizar,
        // "Álvaro" (con tilde) quedaba después de "Zoila" en vez de junto a
        // las demás "A" — este test cubre el fix en normalizarParaOrdenar().
        $this->crearArbitro($colegio, ['usuario' => ['nombreUsuario' => 'Zoila Vargas']]);
        $this->crearArbitro($colegio, ['usuario' => ['nombreUsuario' => 'Álvaro Aguirre']]);

        $html = $this->actingAs($ejecutivo)->get(route('requisitos-documentos-arbitro.index'))
            ->assertOk()
            ->getContent();

        $this->assertGreaterThan(
            strpos($html, 'Álvaro Aguirre'),
            strpos($html, 'Zoila Vargas'),
            '"Álvaro Aguirre" debería listarse antes que "Zoila Vargas" en el select de árbitro específico.',
        );
    }

    public function test_una_vez_aprobado_no_se_puede_reenviar_sin_que_lo_devuelvan(): void
    {
        Storage::fake('local');

        $colegio = $this->crearColegio();
        $arbitro = $this->crearArbitro($colegio);
        $requisito = $this->crearRequisito($arbitro, ['requiereRevision' => false]);

        app(DocumentoArbitroService::class)->guardarEntrega(
            $arbitro,
            $requisito,
            UploadedFile::fake()->create('hoja-vida.pdf', 100, 'application/pdf'),
        );

        $documento = DocumentoArbitro::firstOrFail();
        $this->assertSame(DocumentoArbitro::ESTADO_APROBADO, $documento->estadoRevision);

        $this->actingAs($arbitro->usuario)->get(route('arbitros.mi-perfil'))
            ->assertOk()
            ->assertDontSee('Enviar')
            ->assertSee('Documento aprobado');
    }

    public function test_get_directo_a_un_requisito_redirige_a_la_configuracion_enfocada(): void
    {
        $colegio = $this->crearColegio();
        $ejecutivo = $this->crearEjecutivoConPermisos($colegio);
        $arbitro = $this->crearArbitro($colegio);
        $requisito = $this->crearRequisito($arbitro);

        $this->actingAs($ejecutivo)
            ->get(route('requisitos-documentos-arbitro.show', $requisito->idRequisito))
            ->assertRedirect(route('requisitos-documentos-arbitro.index', ['abrir' => $requisito->idRequisito]).'#requisito-'.$requisito->idRequisito);
    }

    public function test_el_staff_aprueba_y_devuelve_entregas_documentales(): void
    {
        Storage::fake('local');

        $colegio = $this->crearColegio();
        $ejecutivo = $this->crearEjecutivoConPermisos($colegio);
        $arbitro = $this->crearArbitro($colegio);
        $requisito = $this->crearRequisito($arbitro);
        $servicio = app(DocumentoArbitroService::class);

        $documentoAprobado = $servicio->guardarEntrega(
            $arbitro,
            $requisito,
            UploadedFile::fake()->create('hoja-vida.pdf', 120, 'application/pdf'),
        );

        $this->actingAs($ejecutivo)->put(route('documentos.arbitro.aprobar', $documentoAprobado->idDocumento))
            ->assertRedirect();

        $documentoAprobado->refresh();
        $this->assertSame(DocumentoArbitro::ESTADO_APROBADO, $documentoAprobado->estadoRevision);
        $this->assertSame($ejecutivo->idUsuario, $documentoAprobado->idUsuarioRevision);

        $documentoDevuelto = $servicio->guardarEntrega(
            $arbitro,
            $requisito,
            UploadedFile::fake()->create('hoja-vida-corregida.pdf', 120, 'application/pdf'),
        );

        $this->actingAs($ejecutivo)->put(route('documentos.arbitro.devolver', $documentoDevuelto->idDocumento), [
            'comentarioRevision' => 'Falta firma en la segunda pagina.',
        ])->assertRedirect();

        $documentoDevuelto->refresh();
        $this->assertSame(DocumentoArbitro::ESTADO_DEVUELTO, $documentoDevuelto->estadoRevision);
        $this->assertSame('Falta firma en la segunda pagina.', $documentoDevuelto->comentarioRevision);
        $this->assertSame(2, $documentoDevuelto->version);
    }

    public function test_un_colegio_no_descarga_documentos_de_otro_colegio(): void
    {
        Storage::fake('local');

        $colegioA = $this->crearColegio();
        $colegioB = $this->crearColegio();
        $ejecutivoA = $this->crearEjecutivoConPermisos($colegioA);
        $arbitroB = $this->crearArbitro($colegioB);
        $requisitoB = $this->crearRequisito($arbitroB);

        $documentoB = app(DocumentoArbitroService::class)->guardarEntrega(
            $arbitroB,
            $requisitoB,
            UploadedFile::fake()->create('privado.pdf', 80, 'application/pdf'),
        );

        $this->actingAs($ejecutivoA)->get(route('documentos.arbitro.descargar', $documentoB->idDocumento))
            ->assertNotFound();
    }

    public function test_un_arbitro_no_descarga_el_documento_de_otro_arbitro_del_mismo_colegio(): void
    {
        Storage::fake('local');

        $colegio = $this->crearColegio();
        $arbitroDueno = $this->crearArbitro($colegio);
        $arbitroCurioso = $this->crearArbitro($colegio);
        $requisito = $this->crearRequisito($arbitroDueno);

        $documento = app(DocumentoArbitroService::class)->guardarEntrega(
            $arbitroDueno,
            $requisito,
            UploadedFile::fake()->create('privado.pdf', 80, 'application/pdf'),
        );

        $this->actingAs($arbitroCurioso->usuario)
            ->get(route('documentos.arbitro.descargar', $documento->idDocumento))
            ->assertForbidden();

        $this->actingAs($arbitroDueno->usuario)
            ->get(route('documentos.arbitro.descargar', $documento->idDocumento))
            ->assertOk();
    }

    public function test_un_rol_con_solo_ver_arbitros_no_descarga_documentos_ajenos(): void
    {
        Storage::fake('local');

        $colegio = $this->crearColegio();
        $tesorero = $this->crearTesoreroConPermisos($colegio);
        $arbitro = $this->crearArbitro($colegio);
        $requisito = $this->crearRequisito($arbitro);

        $documento = app(DocumentoArbitroService::class)->guardarEntrega(
            $arbitro,
            $requisito,
            UploadedFile::fake()->create('privado.pdf', 80, 'application/pdf'),
        );

        // ver-arbitros (tesorero, designador, sanciones, tecnico) no es
        // suficiente para bajar documentos personales de terceros — solo
        // editar-arbitros (revisor) o el propio dueño.
        $this->actingAs($tesorero)
            ->get(route('documentos.arbitro.descargar', $documento->idDocumento))
            ->assertForbidden();
    }
}

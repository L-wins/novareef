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
            'idCategoria' => $categoria->idCategoria,
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
}

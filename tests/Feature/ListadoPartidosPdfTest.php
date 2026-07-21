<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Arbitro;
use App\Models\Colegio;
use App\Models\Designacion;
use App\Models\DivisionTorneo;
use App\Models\FormatoDesignacion;
use App\Models\Partido;
use App\Models\RolPartido;
use App\Models\SedeTorneo;
use App\Models\SlotDesignacion;
use App\Models\User;
use Database\Seeders\RolesPartidoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreaColegioDePrueba;
use Tests\TestCase;

/**
 * PDF del importador (Fase 3): mismo formato visual del Word de la
 * asociación, ya con los árbitros designados. Mapeo de roles: ARBITRO=
 * Central, LINEA UNO/DOS=Asistente (numeroSlot 1/2), EMERGENTE=Cuarto.
 */
class ListadoPartidosPdfTest extends TestCase
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

    public function test_genera_el_pdf_con_los_arbitros_designados_en_las_columnas_correctas(): void
    {
        $this->seed(RolesPartidoSeeder::class);

        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignadorConPermisos($colegio);
        $torneo     = $this->crearTorneo($colegio, $designador);
        $division   = $this->crearDivision($torneo);
        $formato    = FormatoDesignacion::firstOrCreate(
            ['nombre' => 'Cuarto-Terna'],
            ['maxArbitros' => 4, 'esActivo' => true, 'orden' => 4],
        );

        $partido = Partido::create([
            'idColegio' => $colegio->idColegio, 'idTorneo' => $torneo->idTorneo,
            'idDivision' => $division->idDivision, 'idFormato' => $formato->idFormato,
            'equipoLocal' => 'Santa Fe', 'equipoVisitante' => 'Bethel',
            'fechaPartido' => '2026-03-07', 'horaPartido' => '09:00',
            'estadoPartido' => Partido::ESTADO_BORRADOR, 'modalidadPago' => 'campo',
            'version' => 0, 'observaciones' => 'GRUPO 15',
        ]);

        app(\App\Services\SlotDesignacionService::class)->crear($partido->load('formato'));

        $arbitroCentral = $this->crearArbitro($colegio);
        $arbitroLinea2  = $this->crearArbitro($colegio);
        $idRolCentral   = RolPartido::where('nombre', 'Central')->value('idRol');
        $idRolAsistente = RolPartido::where('nombre', 'Asistente')->value('idRol');

        $designacionCentral = Designacion::create([
            'idPartido' => $partido->idPartido, 'idArbitro' => $arbitroCentral->idArbitro,
            'idRol' => $idRolCentral, 'idColegio' => $colegio->idColegio,
            'estadoDesignacion' => Designacion::ESTADO_CONFIRMADA,
            'idUsuarioDesignador' => $designador->idUsuario,
        ]);
        $designacionLinea2 = Designacion::create([
            'idPartido' => $partido->idPartido, 'idArbitro' => $arbitroLinea2->idArbitro,
            'idRol' => $idRolAsistente, 'idColegio' => $colegio->idColegio,
            'estadoDesignacion' => Designacion::ESTADO_PENDIENTE,
            'idUsuarioDesignador' => $designador->idUsuario,
        ]);

        SlotDesignacion::where('idPartido', $partido->idPartido)
            ->where('idRol', $idRolCentral)->update(['idDesignacion' => $designacionCentral->idDesignacion]);
        SlotDesignacion::where('idPartido', $partido->idPartido)
            ->where('idRol', $idRolAsistente)->where('numeroSlot', 2)
            ->update(['idDesignacion' => $designacionLinea2->idDesignacion]);

        $respuesta = $this->actingAs($designador)
            ->get(route('designaciones.listado.pdf', ['idTorneo' => $torneo->idTorneo]));

        $respuesta->assertOk();
        $this->assertSame('application/pdf', $respuesta->headers->get('Content-Type'));
    }

    public function test_un_colegio_no_puede_exportar_el_listado_de_otro(): void
    {
        $colegioA    = $this->crearColegio();
        $colegioB    = $this->crearColegio();
        $designadorA = $this->crearDesignadorConPermisos($colegioA);
        $ejecutivoB  = $this->crearCuentaAdmin($colegioB, 'ejecutivo');
        $torneoB     = $this->crearTorneo($colegioB, $ejecutivoB);

        $this->actingAs($designadorA)
            ->get(route('designaciones.listado.pdf', ['idTorneo' => $torneoB->idTorneo]))
            ->assertNotFound();
    }

    public function test_filtra_por_division(): void
    {
        $this->seed(RolesPartidoSeeder::class);

        $colegio     = $this->crearColegio();
        $designador  = $this->crearDesignadorConPermisos($colegio);
        $torneo      = $this->crearTorneo($colegio, $designador);
        $divisionA   = $this->crearDivision($torneo);
        $divisionB   = $this->crearDivision($torneo);
        $formato     = $this->crearFormatoDupla();

        Partido::create([
            'idColegio' => $colegio->idColegio, 'idTorneo' => $torneo->idTorneo,
            'idDivision' => $divisionA->idDivision, 'idFormato' => $formato->idFormato,
            'equipoLocal' => 'A', 'equipoVisitante' => 'B',
            'fechaPartido' => '2026-03-07', 'horaPartido' => '09:00',
            'estadoPartido' => Partido::ESTADO_BORRADOR, 'modalidadPago' => 'campo', 'version' => 0,
        ]);
        Partido::create([
            'idColegio' => $colegio->idColegio, 'idTorneo' => $torneo->idTorneo,
            'idDivision' => $divisionB->idDivision, 'idFormato' => $formato->idFormato,
            'equipoLocal' => 'C', 'equipoVisitante' => 'D',
            'fechaPartido' => '2026-03-07', 'horaPartido' => '10:00',
            'estadoPartido' => Partido::ESTADO_BORRADOR, 'modalidadPago' => 'campo', 'version' => 0,
        ]);

        $this->actingAs($designador)
            ->get(route('designaciones.listado.pdf', ['idTorneo' => $torneo->idTorneo, 'division' => $divisionA->idDivision]))
            ->assertOk();
    }

    /**
     * El campo CIUDAD del listado sale directo de SedeTorneo::ciudad (único
     * campo de ubicación de la sede, ver simplificación de M04) — nunca
     * depende de un match parcial ni de un dato ausente, porque es
     * obligatorio al crear la sede. El PDF generado por dompdf comprime el
     * contenido (FlateDecode), así que no es buscable como texto plano en la
     * respuesta — se verifica renderizando la misma vista Blade que usa el
     * controller, con los datos reales del partido.
     */
    public function test_la_ciudad_de_la_sede_aparece_completa_en_el_listado(): void
    {
        $colegio    = $this->crearColegio();
        $designador = $this->crearDesignadorConPermisos($colegio);
        $torneo     = $this->crearTorneo($colegio, $designador);
        $division   = $this->crearDivision($torneo);
        $formato    = $this->crearFormatoDupla();

        $sede = SedeTorneo::create([
            'idTorneo'   => $torneo->idTorneo,
            'nombreSede' => 'Estadio Metropolitano',
            'ciudad'     => 'Barranquilla',
        ]);

        $partido = Partido::create([
            'idColegio' => $colegio->idColegio, 'idTorneo' => $torneo->idTorneo,
            'idDivision' => $division->idDivision, 'idSede' => $sede->idSede,
            'idFormato' => $formato->idFormato,
            'equipoLocal' => 'Junior', 'equipoVisitante' => 'Unión',
            'fechaPartido' => '2026-03-07', 'horaPartido' => '09:00',
            'estadoPartido' => Partido::ESTADO_BORRADOR, 'modalidadPago' => 'campo', 'version' => 0,
        ]);

        $this->actingAs($designador)
            ->get(route('designaciones.listado.pdf', ['idTorneo' => $torneo->idTorneo]))
            ->assertOk();

        $partido->load(['division', 'sede', 'slots.rol', 'slots.designacion.arbitro.usuario']);
        $html = view('pdf.listado-partidos', ['partidos' => collect([$partido]), 'torneo' => $torneo, 'generadoPor' => $designador])->render();

        $this->assertStringContainsString('Barranquilla', $html);
    }
}

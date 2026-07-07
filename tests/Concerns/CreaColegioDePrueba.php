<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Arbitro;
use App\Models\CategoriaArbitro;
use App\Models\Colegio;
use App\Models\DivisionTorneo;
use App\Models\FormatoDesignacion;
use App\Models\Plan;
use App\Models\RolPartido;
use App\Models\SedeTorneo;
use App\Models\Suscripcion;
use App\Models\Torneo;
use App\Models\User;
use App\Services\DesignacionService;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Helpers compartidos para levantar un colegio con plan/suscripción/categoría
 * mínimos en las pruebas de Feature, sin pasar por los controllers HTTP.
 */
trait CreaColegioDePrueba
{
    private function crearPlan(array $overrides = []): Plan
    {
        return Plan::create(array_merge([
            'nombre'       => 'Plan de prueba ' . uniqid(),
            'precio'       => 0,
            'periodicidad' => 'mensual',
            'modulosJSON'  => ['arbitros', 'torneos', 'designaciones'],
            'orden'        => 1,
        ], $overrides));
    }

    private function crearColegio(?Plan $plan = null): Colegio
    {
        $plan ??= $this->crearPlan();

        $tenantId = 'test-' . uniqid();
        DB::table('tenants')->insert(['id' => $tenantId, 'created_at' => now(), 'updated_at' => now()]);

        $colegio = Colegio::create([
            'tenantId'      => $tenantId,
            'nombreColegio' => 'Colegio de prueba',
            'codigoColegio' => 'T-' . uniqid(),
            'emailColegio'  => 'contacto@' . uniqid() . '.test',
            'paisColegio'   => 'Colombia',
        ]);

        Suscripcion::create([
            'idColegio'        => $colegio->idColegio,
            'idPlan'           => $plan->idPlan,
            'fechaInicio'      => today(),
            'fechaVencimiento' => today()->addMonth(),
            'estado'           => 'activa',
        ]);

        CategoriaArbitro::create([
            'idColegio'       => $colegio->idColegio,
            'nombreCategoria' => 'FIFA',
            'esPorDefecto'    => true,
            'activa'          => true,
        ]);

        return $colegio;
    }

    private function crearArbitro(Colegio $colegio, array $overrides = []): Arbitro
    {
        $usuario = User::factory()->create(array_merge([
            'idColegio'  => $colegio->idColegio,
            'rolUsuario' => 'arbitro',
        ], $overrides['usuario'] ?? []));

        return Arbitro::create(array_merge([
            'idUsuario'           => $usuario->idUsuario,
            'idColegio'           => $colegio->idColegio,
            'idCategoria'         => CategoriaArbitro::where('idColegio', $colegio->idColegio)->value('idCategoria'),
            'tipoDocumento'       => 'cedula',
            'numeroDocumento'     => (string) random_int(10000000, 99999999),
            'fechaIngresoColegio' => today(),
            'codigoCarnet'        => 'T-' . random_int(1000000, 9999999),
            'estadoArbitro'       => 'activo',
            // VerificarPerfilCompleto redirige a "completar perfil" mientras
            // pesoArbitro sea null — lo fijamos para poder probar rutas de
            // árbitro (mis-partidos, confirmar/rechazar) sin ese desvío.
            'pesoArbitro'         => 70,
        ], $overrides['arbitro'] ?? []));
    }

    private function crearCuentaAdmin(Colegio $colegio, string $rol, string $estado = 'activo'): User
    {
        return User::factory()->create([
            'idColegio'     => $colegio->idColegio,
            'rolUsuario'    => $rol,
            'estadoUsuario' => $estado,
        ]);
    }

    private function crearTorneo(Colegio $colegio, User $creador, array $overrides = []): Torneo
    {
        return Torneo::create(array_merge([
            'idColegio'          => $colegio->idColegio,
            'nombreTorneo'       => 'Torneo de prueba ' . uniqid(),
            'tipoTorneo'         => 'local',
            'modalidadPago'      => 'campo',
            'estadoTorneo'       => 'activo',
            'organizadorNombre'  => 'Organizador de prueba',
            'temporada'          => (int) date('Y'),
            'fechaInicio'        => today(),
            'fechaFin'           => today()->addMonths(2),
            'idUsuarioCreador'   => $creador->idUsuario,
        ], $overrides));
    }

    private function crearDivision(Torneo $torneo): DivisionTorneo
    {
        return DivisionTorneo::create([
            'idTorneo'       => $torneo->idTorneo,
            'nombreDivision' => 'Primera ' . uniqid(),
        ]);
    }

    private function crearSede(Torneo $torneo): SedeTorneo
    {
        return SedeTorneo::create([
            'idTorneo'    => $torneo->idTorneo,
            'nombreSede'  => 'Sede de prueba',
            'direccion'   => 'Calle 1 # 2-3',
            'municipio'   => 'Tenjo',
        ]);
    }

    /**
     * Formato "Dupla": Central + Asistente (2 árbitros para completar el partido).
     */
    private function crearFormatoDupla(): FormatoDesignacion
    {
        return FormatoDesignacion::firstOrCreate(
            ['nombre' => 'Dupla'],
            ['maxArbitros' => 2, 'esActivo' => true, 'orden' => 1]
        );
    }

    private function crearRolesPartido(): void
    {
        foreach (['Central' => 1, 'Asistente' => 2, 'Cuarto' => 3] as $nombre => $orden) {
            RolPartido::firstOrCreate(['nombre' => $nombre], ['esActivo' => true, 'orden' => $orden]);
        }
    }

    private function idRolPorNombre(string $nombre): int
    {
        return RolPartido::where('nombre', $nombre)->value('idRol');
    }

    private function crearRolSpatie(string $nombre, array $permisos): void
    {
        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        $role->syncPermissions($permisos);
    }

    private function crearDesignador(Colegio $colegio): User
    {
        $this->crearRolSpatie('designador', ['ver-designaciones', 'crear-designaciones']);

        $usuario = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'designador']);
        $usuario->assignRole('designador');

        return $usuario;
    }

    private function crearEjecutivo(Colegio $colegio): User
    {
        $this->crearRolSpatie('ejecutivo', ['ver-designaciones', 'crear-designaciones']);

        $usuario = User::factory()->create(['idColegio' => $colegio->idColegio, 'rolUsuario' => 'ejecutivo']);
        $usuario->assignRole('ejecutivo');

        return $usuario;
    }

    /**
     * Levanta un partido ya publicado (estadoPartido=programado) con dos
     * designaciones pendientes (Central + Asistente), usando el mismo
     * DesignacionService que usan los controllers — sin pasar por HTTP,
     * para no repetir el boilerplate de creación en cada test que solo
     * necesita partir de un partido publicado.
     *
     * @return array{colegio: Colegio, designador: User, partido: Partido,
     *               designacionCentral: Designacion, designacionAsistente: Designacion,
     *               arbitroCentral: Arbitro, arbitroAsistente: Arbitro}
     */
    private function prepararPartidoPublicado(?Colegio $colegio = null, ?User $designador = null): array
    {
        $colegio    ??= $this->crearColegio();
        $designador ??= $this->crearDesignador($colegio);

        $this->crearRolesPartido();
        $formato  = $this->crearFormatoDupla();
        $torneo   = $this->crearTorneo($colegio, $designador);
        $division = $this->crearDivision($torneo);
        $sede     = $this->crearSede($torneo);

        $arbitroCentral   = $this->crearArbitro($colegio);
        $arbitroAsistente = $this->crearArbitro($colegio);

        $servicio = app(DesignacionService::class);

        $partido = $servicio->crearPartido($colegio->idColegio, [
            'idTorneo'        => $torneo->idTorneo,
            'idDivision'      => $division->idDivision,
            'idSede'          => $sede->idSede,
            'idFormato'       => $formato->idFormato,
            'equipoLocal'     => 'Local FC',
            'equipoVisitante' => 'Visitante FC',
            'fechaPartido'    => today()->format('Y-m-d'),
            'horaPartido'     => '15:00',
            'observaciones'   => null,
        ], $designador->idUsuario);

        $resCentral = $servicio->asignarArbitro(
            $partido,
            $arbitroCentral->idArbitro,
            $this->idRolPorNombre('Central'),
            $colegio->idColegio,
            $designador->idUsuario,
        );

        $resAsistente = $servicio->asignarArbitro(
            $partido,
            $arbitroAsistente->idArbitro,
            $this->idRolPorNombre('Asistente'),
            $colegio->idColegio,
            $designador->idUsuario,
        );

        $servicio->publicarPartido($partido->fresh('formato'), $designador);

        return [
            'colegio'              => $colegio,
            'designador'           => $designador,
            'partido'              => $partido->fresh(),
            'designacionCentral'   => $resCentral['designacion'],
            'designacionAsistente' => $resAsistente['designacion'],
            'arbitroCentral'       => $arbitroCentral,
            'arbitroAsistente'     => $arbitroAsistente,
        ];
    }
}

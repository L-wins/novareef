<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Arbitro;
use App\Models\CategoriaArbitro;
use App\Models\Colegio;
use App\Models\Plan;
use App\Models\Suscripcion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
}

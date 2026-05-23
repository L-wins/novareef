<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $planes = [
            [
                'nombre'            => 'Rookie',
                'precio'            => 179000.00,
                'periodicidad'      => 'mensual',
                'limiteArbitros'    => 40,
                'modulosJSON'       => ['arbitros', 'documentos', 'reportes_basicos'],
                'limiteRoles'       => 1,
                'incluyePaginaWeb'  => false,
                'incluyeOnboarding' => false,
                'esVisible'         => true,
                'esActivo'          => true,
                'orden'             => 1,
            ],
            [
                'nombre'            => 'Goliath',
                'precio'            => 389000.00,
                'periodicidad'      => 'mensual',
                'limiteArbitros'    => 100,
                'modulosJSON'       => ['arbitros', 'documentos', 'reportes_basicos', 'reportes_avanzados', 'pagos'],
                'limiteRoles'       => 4,
                'incluyePaginaWeb'  => false,
                'incluyeOnboarding' => false,
                'esVisible'         => true,
                'esActivo'          => true,
                'orden'             => 2,
            ],
            [
                'nombre'            => 'Zenith',
                'precio'            => 579000.00,
                'periodicidad'      => 'mensual',
                'limiteArbitros'    => null,
                'modulosJSON'       => ['arbitros', 'documentos', 'reportes_basicos', 'reportes_avanzados', 'pagos', 'pagina_web', 'onboarding'],
                'limiteRoles'       => 8,
                'incluyePaginaWeb'  => true,
                'incluyeOnboarding' => true,
                'esVisible'         => true,
                'esActivo'          => true,
                'orden'             => 3,
            ],
            [
                'nombre'            => 'GodMode',
                'precio'            => 799000.00,
                'periodicidad'      => 'mensual',
                'limiteArbitros'    => null,
                'modulosJSON'       => ['arbitros', 'documentos', 'reportes_basicos', 'reportes_avanzados', 'pagos', 'pagina_web', 'onboarding', 'api_acceso', 'soporte_prioritario'],
                'limiteRoles'       => null,
                'incluyePaginaWeb'  => true,
                'incluyeOnboarding' => true,
                'esVisible'         => true,
                'esActivo'          => true,
                'orden'             => 4,
            ],
        ];

        foreach ($planes as $datos) {
            Plan::updateOrCreate(['nombre' => $datos['nombre']], $datos);
        }
    }
}

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
                'modulosJSON'       => ['arbitros', 'torneos', 'designaciones'],
                'limiteCuentasAdmin' => 1,
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
                'modulosJSON'       => ['arbitros', 'torneos', 'designaciones', 'finanzas'],
                'limiteCuentasAdmin' => 4,
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
                'modulosJSON'       => ['arbitros', 'torneos', 'designaciones', 'finanzas', 'academico'],
                'limiteCuentasAdmin' => 8,
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
                'modulosJSON'       => ['arbitros', 'torneos', 'designaciones', 'finanzas', 'academico', 'sanciones'],
                'limiteCuentasAdmin' => null,
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

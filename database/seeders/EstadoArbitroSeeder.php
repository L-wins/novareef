<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\EstadoArbitro;
use Illuminate\Database\Seeder;

class EstadoArbitroSeeder extends Seeder
{
    public function run(): void
    {
        $estados = [
            [
                'nombre'          => 'proceso_ingreso',
                'etiqueta'        => 'En proceso de ingreso',
                'color'           => 'blue',
                'descripcion'     => 'El árbitro está completando su perfil y documentación inicial.',
                'permiteDesignar' => false,
                'esActivo'        => true,
                'orden'           => 1,
            ],
            [
                'nombre'          => 'activo',
                'etiqueta'        => 'Activo',
                'color'           => 'green',
                'descripcion'     => 'El árbitro está habilitado para ser designado a partidos.',
                'permiteDesignar' => true,
                'esActivo'        => true,
                'orden'           => 2,
            ],
            [
                'nombre'          => 'inactivo',
                'etiqueta'        => 'Inactivo',
                'color'           => 'gray',
                'descripcion'     => 'El árbitro está temporalmente fuera de servicio por decisión propia o administrativa.',
                'permiteDesignar' => false,
                'esActivo'        => true,
                'orden'           => 3,
            ],
            [
                'nombre'          => 'suspendido',
                'etiqueta'        => 'Suspendido',
                'color'           => 'red',
                'descripcion'     => 'El árbitro está suspendido por sanción disciplinaria.',
                'permiteDesignar' => false,
                'esActivo'        => true,
                'orden'           => 4,
            ],
            [
                'nombre'          => 'retirado',
                'etiqueta'        => 'Retirado',
                'color'           => 'black',
                'descripcion'     => 'El árbitro se ha retirado del arbitraje activo.',
                'permiteDesignar' => false,
                'esActivo'        => true,
                'orden'           => 5,
            ],
        ];

        foreach ($estados as $estado) {
            EstadoArbitro::updateOrCreate(
                ['nombre' => $estado['nombre']],
                $estado,
            );
        }
    }
}

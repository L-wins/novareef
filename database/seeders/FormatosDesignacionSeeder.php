<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\FormatoDesignacion;
use Illuminate\Database\Seeder;

class FormatosDesignacionSeeder extends Seeder
{
    public function run(): void
    {
        $formatos = [
            ['nombre' => 'Solo',         'maxArbitros' => 1, 'orden' => 1, 'descripcion' => 'Un solo árbitro central'],
            ['nombre' => 'Dupla',        'maxArbitros' => 2, 'orden' => 2, 'descripcion' => 'Central y un asistente'],
            ['nombre' => 'Terna',        'maxArbitros' => 3, 'orden' => 3, 'descripcion' => 'Central y dos asistentes'],
            ['nombre' => 'Cuarto-Terna', 'maxArbitros' => 4, 'orden' => 4, 'descripcion' => 'Terna más cuarto árbitro'],
        ];

        foreach ($formatos as $formato) {
            FormatoDesignacion::updateOrCreate(
                ['nombre' => $formato['nombre']],
                $formato + ['esActivo' => true],
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\RolPartido;
use Illuminate\Database\Seeder;

class RolesPartidoSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['nombre' => 'Central',   'descripcion' => 'Árbitro principal del partido',  'orden' => 1],
            ['nombre' => 'Asistente', 'descripcion' => 'Árbitro asistente con bandera',  'orden' => 2],
            ['nombre' => 'Cuarto',    'descripcion' => 'Cuarto árbitro',                 'orden' => 3],
            ['nombre' => 'VAR',       'descripcion' => 'Árbitro de video',               'orden' => 4],
            ['nombre' => 'AVAR',      'descripcion' => 'Asistente del árbitro de video', 'orden' => 5],
        ];

        foreach ($roles as $rol) {
            RolPartido::updateOrCreate(
                ['nombre' => $rol['nombre']],
                $rol + ['esActivo' => true],
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CategoriaArbitro;
use Illuminate\Database\Seeder;

class CategoriaArbitroSeeder extends Seeder
{
    public function run(): void
    {
        $idColegio  = 1;
        $categorias = ['FIFA', 'A', 'A-FEM', 'B', 'C'];

        foreach ($categorias as $nombre) {
            CategoriaArbitro::updateOrCreate(
                [
                    'idColegio'       => $idColegio,
                    'nombreCategoria' => $nombre,
                ],
                [
                    'esPorDefecto' => true,
                    'activa'       => true,
                ],
            );
        }
    }
}

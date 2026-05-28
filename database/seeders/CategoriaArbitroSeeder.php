<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CategoriaArbitro;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriaArbitroSeeder extends Seeder
{
    private const CATEGORIAS_BASE = ['FIFA', 'A', 'A-FEM', 'B', 'C'];

    public function run(): void
    {
        $idColegios = DB::table('colegios')->pluck('idColegio');

        foreach ($idColegios as $idColegio) {
            foreach (self::CATEGORIAS_BASE as $nombre) {
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
}

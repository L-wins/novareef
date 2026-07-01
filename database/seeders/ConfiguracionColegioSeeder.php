<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Colegio;
use App\Models\ConfiguracionColegio;
use Illuminate\Database\Seeder;

class ConfiguracionColegioSeeder extends Seeder
{
    public function run(): void
    {
        Colegio::all()->each(function (Colegio $colegio): void {
            ConfiguracionColegio::firstOrCreate(
                [
                    'idColegio' => $colegio->idColegio,
                    'clave'     => ConfiguracionColegio::DIA_DISPONIBILIDAD,
                ],
                [
                    'valor'       => '1',
                    'descripcion' => 'Día de la semana en que los árbitros deben reportar disponibilidad (1=Lunes ... 7=Domingo)',
                ],
            );

            ConfiguracionColegio::firstOrCreate(
                [
                    'idColegio' => $colegio->idColegio,
                    'clave'     => ConfiguracionColegio::HORAS_LIMITE_CONFIRMACION,
                ],
                [
                    'valor'       => '4',
                    'descripcion' => 'Horas que tiene el árbitro para confirmar una designación antes de que el partido pase a CRÍTICO',
                ],
            );
        });
    }
}

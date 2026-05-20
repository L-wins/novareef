<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Colegio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ColegioSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 'cac-cundinamarca';

        DB::table('tenants')->insertOrIgnore([
            'id'         => $tenantId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Colegio::updateOrCreate(
            ['codigoColegio' => 'CAC-001'],
            [
                'tenantId'            => $tenantId,
                'nombreColegio'       => 'Colegio de Árbitros de Cundinamarca',
                'emailColegio'        => 'contacto@cac.com',
                'telefonoColegio'     => '3001234567',
                'direccionColegio'    => 'Calle 10 # 5-20, Bogotá',
                'ciudadColegio'       => 'Bogotá',
                'departamentoColegio' => 'Cundinamarca',
                'paisColegio'         => 'Colombia',
                'estadoColegio'       => 'activo',
                'planColegio'         => 'profesional',
                'fechaSuscripcion'    => Carbon::today(),
                'fechaExpiracion'     => Carbon::today()->addYear(),
            ]
        );
    }
}

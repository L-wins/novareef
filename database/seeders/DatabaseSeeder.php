<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AdminSeeder::class);

        User::firstOrCreate(
            ['emailUsuario' => 'admin@novareef.test'],
            [
                'nombreUsuario'   => 'Administrador NovaReef',
                'passwordUsuario' => 'password',
                'rolUsuario'      => 'superadmin',
                'estadoUsuario'   => 'activo',
                'temaPreferencia' => 'oscuro',
            ]
        );

        $this->call(ColegioSeeder::class);
        $this->call(CategoriaArbitroSeeder::class);
        $this->call(PlanSeeder::class);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AdminSeeder::class);
        $this->call(RolesPermisosSeeder::class);
        $this->call(VeedorRolSeeder::class);
        $this->call(ColegioSeeder::class);
        $this->call(CategoriaArbitroSeeder::class);
        $this->call(PlanSeeder::class);
        $this->call(RolesPartidoSeeder::class);
        $this->call(FormatosDesignacionSeeder::class);
        $this->call(EstadoArbitroSeeder::class);
        $this->call(SuscripcionColegioSeeder::class);
        $this->call(ConfiguracionColegioSeeder::class);

        if (app()->environment('local')) {
            $this->call(ArbitrosTestSeeder::class);
        }
    }
}

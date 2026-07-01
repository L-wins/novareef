<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class VeedorRolSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Nuevo permiso
        $permiso = Permission::firstOrCreate([
            'name'       => 'crear-calificaciones',
            'guard_name' => 'web',
        ]);

        // Nuevo rol veedor
        $veedor = Role::firstOrCreate(['name' => 'veedor', 'guard_name' => 'web']);
        $veedor->syncPermissions([
            'ver-designaciones',
            'crear-calificaciones',
        ]);

        // Ejecutivo ahora también puede calificar
        $ejecutivo = Role::firstOrCreate(['name' => 'ejecutivo', 'guard_name' => 'web']);
        $ejecutivo->givePermissionTo('crear-calificaciones');
    }
}

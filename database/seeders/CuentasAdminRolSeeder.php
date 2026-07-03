<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CuentasAdminRolSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::firstOrCreate([
            'name'       => 'gestionar-cuentas-admin',
            'guard_name' => 'web',
        ]);

        $ejecutivo = Role::firstOrCreate(['name' => 'ejecutivo', 'guard_name' => 'web']);
        $ejecutivo->givePermissionTo('gestionar-cuentas-admin');
    }
}

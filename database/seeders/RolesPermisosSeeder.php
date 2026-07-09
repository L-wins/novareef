<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermisosSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permisos = [
            // Árbitros
            'ver-arbitros', 'crear-arbitros', 'editar-arbitros',
            // Torneos
            'ver-torneos', 'crear-torneos', 'editar-torneos',
            // Designaciones
            'ver-designaciones', 'crear-designaciones',
            // Finanzas
            'ver-finanzas', 'crear-finanzas', 'editar-finanzas',
            // Académico
            'ver-academico', 'crear-academico',
            // Sanciones
            'ver-sanciones', 'crear-sanciones', 'editar-sanciones',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        //  Roles 

        $ejecutivo = Role::firstOrCreate(['name' => 'ejecutivo', 'guard_name' => 'web']);
        $ejecutivo->syncPermissions($permisos);

        $tesorero = Role::firstOrCreate(['name' => 'tesorero', 'guard_name' => 'web']);
        $tesorero->syncPermissions([
            'ver-arbitros',
            'ver-torneos',
            'ver-designaciones',
            'ver-finanzas', 'crear-finanzas', 'editar-finanzas',
            'ver-sanciones',
        ]);

        $designador = Role::firstOrCreate(['name' => 'designador', 'guard_name' => 'web']);
        $designador->syncPermissions([
            'ver-arbitros',
            'ver-torneos',
            'ver-designaciones', 'crear-designaciones',
        ]);

        $sanciones = Role::firstOrCreate(['name' => 'sanciones', 'guard_name' => 'web']);
        $sanciones->syncPermissions([
            'ver-arbitros',
            'ver-sanciones', 'crear-sanciones', 'editar-sanciones',
        ]);

        $tecnico = Role::firstOrCreate(['name' => 'tecnico', 'guard_name' => 'web']);
        $tecnico->syncPermissions([
            'ver-arbitros',
            'ver-academico', 'crear-academico',
        ]);

        $arbitro = Role::firstOrCreate(['name' => 'arbitro', 'guard_name' => 'web']);
        $arbitro->syncPermissions([
            'ver-academico',
            'ver-sanciones',
        ]);
    }
}

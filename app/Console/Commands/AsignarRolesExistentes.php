<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AsignarRolesExistentes extends Command
{
    protected $signature   = 'novareef:asignar-roles';
    protected $description = 'Asigna roles Spatie a usuarios existentes según su columna rolUsuario';

    /** Roles válidos en Spatie. superadmin se excluye: vive en la tabla admins, no en usuarios. */
    private const ROLES_VALIDOS = [
        'arbitro',
        'ejecutivo',
        'tesorero',
        'designador',
        'sanciones',
        'tecnico',
        'veedor',
    ];

    public function handle(): int
    {
        $total = User::whereIn('rolUsuario', self::ROLES_VALIDOS)->count();

        if ($total === 0) {
            $this->info('No se encontraron usuarios con roles asignables.');
            return self::SUCCESS;
        }

        $this->info("Procesando {$total} usuario(s)...");

        $asignados = 0;
        $omitidos  = 0;
        $filas     = [];

        User::whereIn('rolUsuario', self::ROLES_VALIDOS)
            ->select(['idUsuario', 'nombreUsuario', 'rolUsuario'])
            ->chunkById(200, function ($chunk) use (&$asignados, &$omitidos, &$filas): void {
                foreach ($chunk as $usuario) {
                    if ($usuario->hasRole($usuario->rolUsuario)) {
                        $filas[] = [$usuario->idUsuario, $usuario->nombreUsuario, $usuario->rolUsuario, 'Sin cambios'];
                        $omitidos++;
                        continue;
                    }

                    $usuario->syncRoles([$usuario->rolUsuario]);
                    $filas[] = [$usuario->idUsuario, $usuario->nombreUsuario, $usuario->rolUsuario, '✓ Asignado'];
                    $asignados++;
                }
            }, 'idUsuario');

        $this->table(['ID', 'Nombre', 'Rol', 'Resultado'], $filas);
        $this->newLine();
        $this->info("Asignados: {$asignados}  |  Sin cambios: {$omitidos}");

        return self::SUCCESS;
    }
}

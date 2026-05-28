<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AsignarRolesExistentes extends Command
{
    protected $signature   = 'novareef:asignar-roles';
    protected $description = 'Asigna roles Spatie a usuarios existentes según su columna rolUsuario';

    private const MAPA = [
        'arbitro'    => 'arbitro',
        'ejecutivo'  => 'ejecutivo',
        'tesorero'   => 'tesorero',
        'designador' => 'designador',
        'sanciones'  => 'sanciones',
        'tecnico'    => 'tecnico',
    ];

    public function handle(): int
    {
        $usuarios = User::all();

        if ($usuarios->isEmpty()) {
            $this->info('No se encontraron usuarios en la tabla.');
            return self::SUCCESS;
        }

        $procesados = [];
        $asignados  = 0;
        $omitidos   = 0;

        foreach ($usuarios as $usuario) {
            $rolSpatie = self::MAPA[$usuario->rolUsuario] ?? null;

            if (! $rolSpatie) {
                $procesados[] = [
                    $usuario->idUsuario,
                    $usuario->nombreUsuario,
                    $usuario->rolUsuario,
                    'Omitido — rol sin equivalencia Spatie',
                ];
                $omitidos++;
                continue;
            }

            if ($usuario->hasRole($rolSpatie)) {
                $procesados[] = [
                    $usuario->idUsuario,
                    $usuario->nombreUsuario,
                    $rolSpatie,
                    'Sin cambios — ya tenía el rol',
                ];
                $omitidos++;
                continue;
            }

            $usuario->assignRole($rolSpatie);
            $procesados[] = [
                $usuario->idUsuario,
                $usuario->nombreUsuario,
                $rolSpatie,
                '✓ Rol asignado',
            ];
            $asignados++;
        }

        $this->table(
            ['ID', 'Nombre', 'Rol Spatie', 'Resultado'],
            $procesados
        );

        $this->newLine();
        $this->info("Roles asignados: {$asignados}  |  Omitidos: {$omitidos}");

        return self::SUCCESS;
    }
}

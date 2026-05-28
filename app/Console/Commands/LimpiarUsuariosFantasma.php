<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class LimpiarUsuariosFantasma extends Command
{
    protected $signature   = 'novareef:limpiar-fantasmas';
    protected $description = 'Elimina usuarios con rolUsuario=superadmin de la tabla usuarios (son fantasmas — el superadmin real vive en admins)';

    public function handle(): int
    {
        $fantasmas = User::withTrashed()
            ->where('rolUsuario', 'superadmin')
            ->get();

        if ($fantasmas->isEmpty()) {
            $this->info('No se encontraron usuarios fantasma (rolUsuario=superadmin).');
            return self::SUCCESS;
        }

        $this->warn("Se encontraron {$fantasmas->count()} usuario(s) fantasma:");

        $this->table(
            ['ID', 'Nombre', 'Email', 'Estado'],
            $fantasmas->map(fn ($u) => [
                $u->idUsuario,
                $u->nombreUsuario,
                $u->emailUsuario,
                $u->deleted_at ? 'soft-deleted' : $u->estadoUsuario,
            ])->toArray()
        );

        if (! $this->confirm('¿Eliminar permanentemente estos usuarios?', false)) {
            $this->info('Operación cancelada.');
            return self::SUCCESS;
        }

        $fantasmas->each(fn ($u) => $u->forceDelete());

        $this->info("{$fantasmas->count()} usuario(s) eliminado(s) permanentemente.");
        return self::SUCCESS;
    }
}

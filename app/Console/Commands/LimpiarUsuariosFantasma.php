<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class LimpiarUsuariosFantasma extends Command
{
    protected $signature = 'novareef:limpiar-fantasmas
                            {--force : Eliminar sin pedir confirmación interactiva (útil en CI/scripts)}';

    protected $description = 'Elimina permanentemente usuarios con rolUsuario=superadmin (fantasmas — el superadmin real vive en la tabla admins)';

    public function handle(): int
    {
        $fantasmas = User::withTrashed()
            ->where('rolUsuario', 'superadmin')
            ->get(['idUsuario', 'nombreUsuario', 'emailUsuario', 'estadoUsuario', 'deleted_at']);

        if ($fantasmas->isEmpty()) {
            $this->info('No se encontraron usuarios fantasma (rolUsuario=superadmin). Todo limpio.');
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

        $confirmar = $this->option('force')
            || $this->confirm('¿Eliminar permanentemente estos usuarios?', false);

        if (! $confirmar) {
            $this->info('Operación cancelada.');
            return self::SUCCESS;
        }

        $count = $fantasmas->count();
        $fantasmas->each(fn ($u) => $u->forceDelete());

        $this->info("{$count} usuario(s) eliminado(s) permanentemente.");
        return self::SUCCESS;
    }
}

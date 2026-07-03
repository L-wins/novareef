<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CuentaAdminService
{
    public function __construct(
        private readonly ArbitroService $credenciales,
        private readonly LimiteService  $limites,
    ) {}

    /**
     * Crea una cuenta admin interna del colegio (tesorero, designador, sanciones,
     * tecnico, veedor, o un ejecutivo adicional). Sin email propio: las credenciales
     * se notifican al correo del colegio, y el login se hace por usernameUsuario.
     *
     * @throws \RuntimeException  Si se alcanzó el límite de cuentas admin del plan,
     *                            o si el username/email ya está en uso.
     */
    public function crear(
        int     $idColegio,
        string  $nombreColegio,
        string  $urlAcceso,
        string  $nombre,
        string  $username,
        ?string $email,
        string  $emailColegioNotificacion,
        string  $rol,
    ): User {
        $this->limites->asegurarPuedeCrearCuentaAdmin($idColegio);

        return $this->credenciales->registrarConCredenciales(
            idColegio:         $idColegio,
            nombre:            $nombre,
            email:             $email,
            telefono:          '',
            rol:               $rol,
            nombreColegio:     $nombreColegio,
            urlAcceso:         $urlAcceso,
            usernameUsuario:   $username,
            emailNotificacion: $emailColegioNotificacion,
        );
    }

    /** Revoca el acceso de la cuenta sin borrar el registro (bloquea el login). */
    public function revocar(User $usuario): void
    {
        $usuario->update(['estadoUsuario' => 'inactivo']);
    }

    /**
     * Reactiva una cuenta revocada — vuelve a chequear el cupo del plan,
     * porque revocar libera espacio y otra cuenta pudo haber ocupado ese cupo.
     *
     * @throws \RuntimeException  Si no hay cupo disponible.
     */
    public function reactivar(User $usuario): void
    {
        $this->limites->asegurarPuedeCrearCuentaAdmin($usuario->idColegio);

        $usuario->update(['estadoUsuario' => 'activo']);
    }

    /**
     * @param  array<string, mixed>  $datos  Datos ya validados (UpdateCuentaAdminRequest).
     */
    public function actualizar(User $usuario, array $datos): void
    {
        DB::transaction(function () use ($usuario, $datos): void {
            $usuario->update($datos);

            if (isset($datos['rolUsuario'])) {
                $usuario->syncRoles([$datos['rolUsuario']]);
            }
        });
    }
}

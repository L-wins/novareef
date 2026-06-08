<?php

declare(strict_types=1);

namespace App\Actions;

use App\Mail\CredencialesColegioMail;
use App\Models\User;
use App\Support\PasswordGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

final class RegistrarUsuarioConCredenciales
{
    /**
     * Genera contraseña, crea el User, asigna rol Spatie y programa el envío
     * del correo de credenciales.
     *
     * Garantías:
     *   - User::create() y assignRole() son atómicos (transacción propia con savepoint
     *     si el caller ya tiene una activa, lo que hace a este Action seguro en cualquier contexto).
     *   - El mail se despacha solo si TODAS las transacciones anidadas confirman.
     *   - Un fallo de mail no hace rollback del usuario ya creado.
     *
     * @throws \InvalidArgumentException  Si el rol no existe en Spatie (guard web).
     * @throws \RuntimeException          Si el email ya está en uso (incluyendo soft-deleted).
     */
    public function ejecutar(
        int    $idColegio,
        string $nombre,
        string $email,
        string $telefono,
        string $rol,
        string $nombreColegio,
        string $urlAcceso,
    ): User {
        // ── Validaciones previas (antes de tocar la BD) ───────────────────────
        $this->validarRol($rol);
        $this->validarEmailUnico($email);

        // ── Generar contraseña una sola vez ───────────────────────────────────
        $passwordPlano = PasswordGenerator::generate();

        // ── Transacción propia ────────────────────────────────────────────────
        // DB::transaction() anida automáticamente con savepoints si ya hay una
        // transacción activa en el caller (ArbitroController, ColegioController).
        // DB::afterCommit() espera a que TODAS las transacciones externas confirmen.
        return DB::transaction(function () use (
            $idColegio, $nombre, $email, $telefono, $rol,
            $nombreColegio, $urlAcceso, $passwordPlano,
        ): User {
            $usuario = User::create([
                'idColegio'            => $idColegio,
                'nombreUsuario'        => $nombre,
                'emailUsuario'         => $email,
                'passwordUsuario'      => $passwordPlano, // cast 'hashed' lo hashea automáticamente
                'telefonoUsuario'      => $telefono,
                'rolUsuario'           => $rol,
                'estadoUsuario'        => 'activo',
                'must_change_password' => true,
            ]);

            $usuario->assignRole($rol);

            DB::afterCommit(function () use ($email, $passwordPlano, $nombreColegio, $urlAcceso, $usuario): void {
                try {
                    Mail::to($email)->send(
                        new CredencialesColegioMail(
                            nombreColegio:    $nombreColegio,
                            urlAcceso:        $urlAcceso,
                            emailAdmin:       $email,
                            passwordGenerado: $passwordPlano,
                        )
                    );
                } catch (\Throwable $e) {
                    // El usuario ya existe — no hacer rollback. Solo registrar el fallo.
                    Log::error('No se pudo enviar email de credenciales', [
                        'idUsuario' => $usuario->idUsuario,
                        'email'     => $email,
                        'error'     => $e->getMessage(),
                    ]);
                }
            });

            return $usuario;
        });
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

    private function validarRol(string $rol): void
    {
        $existe = Role::where('name', $rol)
                      ->where('guard_name', 'web')
                      ->exists();

        if (! $existe) {
            throw new \InvalidArgumentException(
                "El rol '{$rol}' no existe en el guard 'web'."
            );
        }
    }

    private function validarEmailUnico(string $email): void
    {
        // withTrashed() evita reusar emails de usuarios eliminados (soft delete).
        $existe = User::withTrashed()
                      ->where('emailUsuario', $email)
                      ->exists();

        if ($existe) {
            throw new \RuntimeException(
                "El email '{$email}' ya está registrado en el sistema."
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\CredencialesColegioMail;
use App\Models\Arbitro;
use App\Models\HistorialEstadoArbitro;
use App\Models\User;
use App\Support\PasswordGenerator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

final class ArbitroService
{
    /** Campos de autoservicio (MiPerfilRequest) que pertenecen al modelo User, no al Arbitro. */
    private const CAMPOS_PERFIL_USUARIO = ['telefonoUsuario'];

    /**
     * Registra el árbitro completo: crea su usuario con credenciales
     * (delegado a registrarConCredenciales) y el registro de árbitro asociado.
     *
     * @throws \InvalidArgumentException  Si el rol 'arbitro' no existe en Spatie.
     * @throws \RuntimeException          Si el email ya está en uso.
     */
    public function registrar(
        int     $idColegio,
        string  $nombreColegio,
        string  $urlAcceso,
        string  $nombreUsuario,
        string  $emailUsuario,
        ?string $telefonoUsuario,
        int     $idCategoria,
        string  $tipoDocumento,
        string  $numeroDocumento,
        string  $fechaIngresoColegio,
        ?string $lugarExpedicionCC,
    ): Arbitro {
        return DB::transaction(function () use (
            $idColegio, $nombreColegio, $urlAcceso, $nombreUsuario, $emailUsuario,
            $telefonoUsuario, $idCategoria, $tipoDocumento, $numeroDocumento,
            $fechaIngresoColegio, $lugarExpedicionCC,
        ): Arbitro {
            $usuario = $this->registrarConCredenciales(
                idColegio:     $idColegio,
                nombre:        $nombreUsuario,
                email:         $emailUsuario,
                telefono:      $telefonoUsuario ?? '',
                rol:           'arbitro',
                nombreColegio: $nombreColegio,
                urlAcceso:     $urlAcceso,
            );

            return Arbitro::create([
                'idUsuario'           => $usuario->idUsuario,
                'idColegio'           => $idColegio,
                'idCategoria'         => $idCategoria,
                'tipoDocumento'       => $tipoDocumento,
                'numeroDocumento'     => $numeroDocumento,
                'fechaIngresoColegio' => $fechaIngresoColegio,
                'lugarExpedicionCC'   => $lugarExpedicionCC,
            ]);
        });
    }

    /**
     * Actualiza los datos del usuario asociado y del árbitro en una sola transacción.
     * La contraseña solo se toca si viene un valor nuevo — nunca se sobrescribe con vacío.
     *
     * @param  array<string, mixed>  $datos  Datos ya validados (UpdateArbitroRequest).
     */
    public function actualizar(Arbitro $arbitro, array $datos): void
    {
        DB::transaction(function () use ($arbitro, $datos): void {
            $datosUsuario = [
                'nombreUsuario'   => $datos['nombreUsuario'],
                'emailUsuario'    => $datos['emailUsuario'],
                'telefonoUsuario' => $datos['telefonoUsuario'] ?? null,
            ];

            if (! empty($datos['passwordUsuario'])) {
                $datosUsuario['passwordUsuario'] = $datos['passwordUsuario'];
            }

            $arbitro->usuario->update($datosUsuario);

            $arbitro->update(
                collect($datos)->except(['nombreUsuario', 'emailUsuario', 'telefonoUsuario', 'passwordUsuario'])->toArray()
            );
        });
    }

    /**
     * Actualiza el perfil del propio árbitro (autoservicio): separa los campos
     * de usuario (teléfono) del resto — datos físicos, vehículo, dirección —
     * que pertenecen al árbitro. Usado tanto por "mi perfil" como por el wizard
     * de completar perfil en el primer acceso; ambos comparten las mismas reglas
     * (MiPerfilRequest) y el mismo reparto de campos.
     *
     * @param  array<string, mixed>  $datos  Datos ya validados (MiPerfilRequest).
     */
    public function actualizarPerfilPropio(Arbitro $arbitro, array $datos): void
    {
        DB::transaction(function () use ($arbitro, $datos): void {
            $arbitro->usuario->update(collect($datos)->only(self::CAMPOS_PERFIL_USUARIO)->toArray());
            $arbitro->update(collect($datos)->except(self::CAMPOS_PERFIL_USUARIO)->toArray());
        });
    }

    /**
     * Cambia el estado del árbitro y registra el movimiento en su historial.
     *
     * @throws \RuntimeException  Si el árbitro ya se encuentra en ese estado.
     */
    public function cambiarEstado(
        Arbitro $arbitro,
        string  $estadoNuevo,
        ?string $motivo      = null,
        ?string $fechaInicio = null,
        ?string $fechaFin    = null,
    ): void {
        if ($arbitro->estadoArbitro === $estadoNuevo) {
            throw new \RuntimeException('El árbitro ya tiene ese estado.');
        }

        DB::transaction(function () use ($arbitro, $estadoNuevo, $motivo, $fechaInicio, $fechaFin): void {
            $this->registrarHistorial($arbitro, $estadoNuevo, $motivo, $fechaInicio, $fechaFin);
            $arbitro->update(['estadoArbitro' => $estadoNuevo]);
        });
    }

    /**
     * Archiva al árbitro: lo marca 'retirado', desactiva su cuenta de usuario,
     * registra el motivo en el historial y aplica soft-delete sobre el registro.
     */
    public function archivar(Arbitro $arbitro, ?string $motivo): void
    {
        DB::transaction(function () use ($arbitro, $motivo): void {
            $this->registrarHistorial($arbitro, 'retirado', $motivo);
            $arbitro->update(['estadoArbitro' => 'retirado']);
            $arbitro->usuario?->update(['estadoUsuario' => 'inactivo']);
            $arbitro->delete();
        });
    }

    /**
     * Restaura a un árbitro archivado: revierte el soft-delete, reactiva su cuenta
     * de usuario y lo deja en 'inactivo' — requiere revisión antes de volver a
     * quedar disponible para designaciones.
     */
    public function restaurar(Arbitro $arbitro): void
    {
        DB::transaction(function () use ($arbitro): void {
            $arbitro->restore();
            $arbitro->update(['estadoArbitro' => 'inactivo']);
            $arbitro->usuario?->update(['estadoUsuario' => 'activo']);
            $this->registrarHistorial($arbitro, 'inactivo', 'Árbitro restaurado', estadoAnterior: 'retirado');
        });
    }

    /**
     * Genera contraseña, crea el User, asigna rol Spatie y programa el envío
     * del correo de credenciales.
     *
     * Garantías:
     *   - User::create() y assignRole() son atómicos (transacción propia con savepoint
     *     si el caller ya tiene una activa, lo que hace este método seguro en cualquier contexto).
     *   - El mail se despacha solo si TODAS las transacciones anidadas confirman.
     *   - Un fallo de mail no hace rollback del usuario ya creado.
     *
     * @throws \InvalidArgumentException  Si el rol no existe en Spatie (guard web).
     * @throws \RuntimeException          Si el email ya está en uso (incluyendo soft-deleted).
     */
    public function registrarConCredenciales(
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

    /**
     * Centraliza la creación del historial de estado — usado por cambiarEstado,
     * archivar y restaurar.
     */
    private function registrarHistorial(
        Arbitro $arbitro,
        string  $estadoNuevo,
        ?string $motivo         = null,
        ?string $fechaInicio    = null,
        ?string $fechaFin       = null,
        ?string $estadoAnterior = null,
    ): void {
        HistorialEstadoArbitro::create([
            'idArbitro'       => $arbitro->idArbitro,
            'idUsuarioCambio' => Auth::id(),
            'estadoAnterior'  => $estadoAnterior ?? $arbitro->estadoArbitro,
            'estadoNuevo'     => $estadoNuevo,
            'motivo'          => $motivo,
            'fechaInicio'     => $fechaInicio,
            'fechaFin'        => $fechaFin,
        ]);
    }
}

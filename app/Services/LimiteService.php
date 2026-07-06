<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Arbitro;
use App\Models\Colegio;
use App\Models\Plan;
use App\Models\User;

final class LimiteService
{
    /** Roles que consumen cupo del límite de cuentas admin del plan (todo menos árbitro). */
    public const ROLES_ADMIN = ['ejecutivo', 'tesorero', 'designador', 'sanciones', 'tecnico', 'veedor'];

    public function arbitrosActivos(int $idColegio): int
    {
        return Arbitro::where('idColegio', $idColegio)->count();
    }

    public function limiteArbitros(int $idColegio): ?int
    {
        $plan = $this->plan($idColegio);

        // Sin plan resoluble (colegio suspendido/sin suscripción vigente): fail-safe, bloquear.
        // Con plan pero limiteArbitros=NULL en BD: es el valor real de "ilimitado", no un default.
        return $plan === null ? 0 : $plan->limiteArbitros;
    }

    public function puedeCrearArbitro(int $idColegio): bool
    {
        $limite = $this->limiteArbitros($idColegio);

        return $limite === null || $this->arbitrosActivos($idColegio) < $limite;
    }

    public function puedeReactivarArbitro(int $idColegio): bool
    {
        return $this->puedeCrearArbitro($idColegio);
    }

    public function cuentasAdminActivas(int $idColegio): int
    {
        return User::where('idColegio', $idColegio)
            ->whereIn('rolUsuario', self::ROLES_ADMIN)
            ->where('estadoUsuario', 'activo')
            ->count();
    }

    public function limiteCuentasAdmin(int $idColegio): ?int
    {
        $plan = $this->plan($idColegio);

        return $plan === null ? 0 : $plan->limiteCuentasAdmin;
    }

    public function puedeCrearCuentaAdmin(int $idColegio): bool
    {
        $limite = $this->limiteCuentasAdmin($idColegio);

        return $limite === null || $this->cuentasAdminActivas($idColegio) < $limite;
    }

    public function porcentajeUsoCuentasAdmin(int $idColegio): float
    {
        $limite = $this->limiteCuentasAdmin($idColegio);

        if ($limite === null || $limite === 0) {
            return 0.0;
        }

        return round(($this->cuentasAdminActivas($idColegio) / $limite) * 100, 1);
    }

    public function porcentajeUsoArbitros(int $idColegio): float
    {
        $limite = $this->limiteArbitros($idColegio);

        if ($limite === null || $limite === 0) {
            return 0.0;
        }

        return round(($this->arbitrosActivos($idColegio) / $limite) * 100, 1);
    }

    public function moduloHabilitado(int $idColegio, string $modulo): bool
    {
        return in_array($modulo, $this->modulosHabilitados($idColegio), true);
    }

    /** @return list<string> Módulos habilitados por el plan vigente del colegio (vacío si no hay plan resoluble). */
    public function modulosHabilitados(int $idColegio): array
    {
        return $this->plan($idColegio)?->modulosJSON ?? [];
    }

    /** @throws \RuntimeException  Si el colegio ya alcanzó el límite de árbitros de su plan. */
    public function asegurarPuedeCrearArbitro(int $idColegio): void
    {
        if (! $this->puedeCrearArbitro($idColegio)) {
            throw new \RuntimeException(
                "Alcanzaste el límite de árbitros de tu plan actual ({$this->limiteArbitros($idColegio)}). Actualiza tu plan para registrar más árbitros."
            );
        }
    }

    /** @throws \RuntimeException  Si no hay cupo disponible para reactivar el árbitro. */
    public function asegurarPuedeReactivarArbitro(int $idColegio): void
    {
        if (! $this->puedeReactivarArbitro($idColegio)) {
            throw new \RuntimeException(
                "No hay cupo disponible para reactivar este árbitro — alcanzaste el límite de tu plan ({$this->limiteArbitros($idColegio)}). Actualiza tu plan o retira otro árbitro primero."
            );
        }
    }

    /** @throws \RuntimeException  Si el colegio ya alcanzó el límite de cuentas admin de su plan. */
    public function asegurarPuedeCrearCuentaAdmin(int $idColegio): void
    {
        if (! $this->puedeCrearCuentaAdmin($idColegio)) {
            throw new \RuntimeException(
                "Alcanzaste el límite de cuentas administrativas de tu plan actual ({$this->limiteCuentasAdmin($idColegio)}). Actualiza tu plan para crear más cuentas."
            );
        }
    }

    private function plan(int $idColegio): ?Plan
    {
        return Colegio::find($idColegio)?->plan;
    }
}

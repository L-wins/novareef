<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Admin;
use App\Models\AdminActionLog;

final class AdminAuditService
{
    /**
     * Registra una acción de un admin sobre una entidad (colegio, plan,
     * suscripción, usuario). A diferencia de admin_login_logs (solo
     * intentos de acceso), esto deja rastro de qué cambió y quién lo hizo.
     */
    public function registrar(Admin $admin, string $accion, string $entidad, ?int $entidadId = null, ?string $detalle = null): AdminActionLog
    {
        return AdminActionLog::create([
            'idAdmin'   => $admin->idAdmin,
            'accion'    => $accion,
            'entidad'   => $entidad,
            'entidadId' => $entidadId,
            'detalle'   => $detalle,
        ]);
    }
}

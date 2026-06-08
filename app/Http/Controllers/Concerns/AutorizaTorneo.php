<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Models\Torneo;

/**
 * Trait compartido por todos los controladores del módulo Torneo.
 * Centraliza la verificación de que el torneo pertenece al colegio activo.
 * Requiere que la clase que lo use implemente idColegioActivo() — incluir ResuelveColegio.
 */
trait AutorizaTorneo
{
    protected function autorizarTorneo(Torneo $torneo): void
    {
        abort_unless(
            (int) $torneo->idColegio === $this->idColegioActivo(),
            403,
            'Este torneo pertenece a otro colegio.',
        );
    }
}

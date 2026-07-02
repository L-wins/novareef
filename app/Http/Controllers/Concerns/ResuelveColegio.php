<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Models\Arbitro;
use Illuminate\Support\Facades\Auth;

/**
 * Trait compartido por todos los controladores del panel de usuario que necesitan
 * resolver el colegio o el árbitro del usuario autenticado.
 */
trait ResuelveColegio
{
    protected function idColegioActivo(): int
    {
        $idColegio = Auth::user()?->idColegio;

        abort_if($idColegio === null, 403, 'Tu cuenta no tiene un colegio asignado.');

        return (int) $idColegio;
    }

    /**
     * Devuelve el árbitro del usuario autenticado verificando que pertenezca
     * al colegio activo. Centraliza la query usada en DisponibilidadController
     * y ArbitroPerfilController.
     */
    protected function arbitroAutenticado(array $relaciones = []): Arbitro
    {
        return Arbitro::with($relaciones)
            ->where('idUsuario', Auth::id())
            ->where('idColegio', $this->idColegioActivo())
            ->firstOrFail();
    }
}

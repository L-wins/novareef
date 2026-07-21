<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AceptacionPolitica;
use App\Models\User;

/**
 * Ley 1581 de 2012 (Habeas Data, Colombia): exige consentimiento previo,
 * expreso e informado antes de tratar datos personales, y uno separado para
 * datos sensibles (art. 5 — acá, rhArbitro/epsArbitro son datos de salud).
 * VERSION_ACTUAL se sube cada vez que cambia el contenido de la política —
 * eso vuelve a pedir aceptación a todos, porque lo que aceptaron antes ya
 * no es lo que dice el documento vigente.
 */
final class PoliticaPrivacidadService
{
    public const VERSION_ACTUAL = '2026-07-21';

    public function debeAceptarGeneral(User $usuario): bool
    {
        return ! AceptacionPolitica::where('idUsuario', $usuario->idUsuario)
            ->where('tipo', 'politica_general')
            ->where('version', self::VERSION_ACTUAL)
            ->exists();
    }

    public function haAceptadoDatosSensibles(User $usuario): bool
    {
        return AceptacionPolitica::where('idUsuario', $usuario->idUsuario)
            ->where('tipo', 'datos_sensibles')
            ->exists();
    }

    public function registrarAceptacionGeneral(User $usuario, ?string $ip): void
    {
        AceptacionPolitica::firstOrCreate([
            'idUsuario' => $usuario->idUsuario,
            'tipo'      => 'politica_general',
            'version'   => self::VERSION_ACTUAL,
        ], ['ip' => $ip]);
    }

    public function registrarAceptacionDatosSensibles(User $usuario, ?string $ip): void
    {
        AceptacionPolitica::firstOrCreate([
            'idUsuario' => $usuario->idUsuario,
            'tipo'      => 'datos_sensibles',
            'version'   => 'na',
        ], ['ip' => $ip]);
    }
}

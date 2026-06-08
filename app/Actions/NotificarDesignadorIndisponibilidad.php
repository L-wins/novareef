<?php

declare(strict_types=1);

namespace App\Actions;

use App\Mail\IndisponibilidadExtraordinariaMail;
use App\Models\Arbitro;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Notifica al designador (o ejecutivo) del colegio cuando un árbitro
 * registra una indisponibilidad extraordinaria con partidos confirmados.
 *
 * Separado del controlador para que pueda reutilizarse desde otros contextos
 * (commands, jobs, etc.) sin depender del ciclo HTTP.
 */
final class NotificarDesignadorIndisponibilidad
{
    /**
     * @param  Collection<int, \App\Models\Designacion>  $partidosAfectados
     */
    public function ejecutar(
        Arbitro    $arbitro,
        string     $fecha,
        string     $franja,
        string     $motivo,
        Collection $partidosAfectados,
    ): void {
        if ($partidosAfectados->isEmpty()) {
            return;
        }

        $designador = User::where('idColegio', $arbitro->idColegio)
            ->whereIn('rolUsuario', ['ejecutivo', 'designador'])
            ->first();

        if ($designador === null) {
            return;
        }

        try {
            Mail::to($designador->emailUsuario)->send(
                new IndisponibilidadExtraordinariaMail($arbitro, $fecha, $franja, $motivo, $partidosAfectados)
            );
        } catch (\Throwable $e) {
            Log::error('[NotificarDesignadorIndisponibilidad] Error enviando mail', [
                'idArbitro'  => $arbitro->idArbitro,
                'idColegio'  => $arbitro->idColegio,
                'fecha'      => $fecha,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}

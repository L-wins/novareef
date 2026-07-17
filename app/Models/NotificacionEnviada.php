<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

/**
 * Ledger de deduplicación para los Notificar*Job — ver migración
 * create_notificaciones_enviadas_table para el porqué.
 */
final class NotificacionEnviada extends Model
{
    protected $table      = 'notificaciones_enviadas';
    protected $primaryKey = 'idNotificacionEnviada';
    public    $timestamps = false;

    protected $fillable = ['tipoNotificacion', 'referenciaNotificacion', 'destinatario'];

    /**
     * Reclama el envío de forma atómica: true si es la primera vez (el job
     * debe enviar), false si ya se reclamó antes (el job debe omitir el
     * envío — es un reintento, no una notificación nueva). La garantía es
     * el índice único de la tabla, no este chequeo en PHP: dos workers
     * reclamando al mismo tiempo solo dejan que uno gane la inserción.
     */
    public static function reclamar(string $tipo, string $referencia, string $destinatario): bool
    {
        try {
            self::create([
                'tipoNotificacion'       => $tipo,
                'referenciaNotificacion' => $referencia,
                'destinatario'           => $destinatario,
            ]);

            return true;
        } catch (QueryException $e) {
            if ((int) $e->getCode() === 23000) {
                return false;
            }

            throw $e;
        }
    }
}

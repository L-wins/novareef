<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ledger genérico de deduplicación para los Notificar*Job — sin esto, un
 * reintento de cola tras una caída parcial del worker (job "falla" después
 * de que Mail::send() ya llegó a destino) reenvía el mismo correo/SMS. Cada
 * job reclama (tipo, referencia, destinatario) de forma atómica antes de
 * enviar; el índice único es la garantía real, no un chequeo en PHP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones_enviadas', static function (Blueprint $table): void {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idNotificacionEnviada');

            $table->string('tipoNotificacion', 40);
            $table->string('referenciaNotificacion', 60);
            $table->string('destinatario', 190);

            $table->timestamp('created_at')->useCurrent();

            $table->unique(
                ['tipoNotificacion', 'referenciaNotificacion', 'destinatario'],
                'uq_notificacion_enviada'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones_enviadas');
    }
};

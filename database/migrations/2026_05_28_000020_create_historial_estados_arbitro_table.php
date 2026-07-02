<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_estados_arbitro', function (Blueprint $table) {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idHistorial');

            $table->unsignedBigInteger('idArbitro');
            $table->unsignedBigInteger('idUsuarioCambio');

            $table->string('estadoAnterior', 30);
            $table->string('estadoNuevo', 30);
            $table->text('motivo')->nullable();
            $table->date('fechaInicio')->nullable();
            $table->date('fechaFin')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->foreign('idArbitro')
                  ->references('idArbitro')
                  ->on('arbitros')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');

            $table->foreign('idUsuarioCambio')
                  ->references('idUsuario')
                  ->on('usuarios')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->index('idArbitro');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('historial_estados_arbitro', function (Blueprint $table) {
            $table->dropForeign(['idArbitro']);
            $table->dropForeign(['idUsuarioCambio']);
        });

        Schema::dropIfExists('historial_estados_arbitro');
    }
};

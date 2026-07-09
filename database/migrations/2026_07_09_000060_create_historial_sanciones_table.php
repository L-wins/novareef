<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_sanciones', static function (Blueprint $table): void {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idHistorial');

            $table->unsignedBigInteger('idSancion');
            $table->foreign('idSancion')
                  ->references('idSancion')->on('sanciones')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('idColegio');
            $table->foreign('idColegio')
                  ->references('idColegio')->on('colegios')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('idArbitro');
            $table->foreign('idArbitro')
                  ->references('idArbitro')->on('arbitros')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('idUsuarioAccion')->nullable();
            $table->foreign('idUsuarioAccion')
                  ->references('idUsuario')->on('usuarios')
                  ->onDelete('set null');

            $table->enum('tipoAccion', ['impuesta', 'cumplida', 'anulada', 'apelada', 'apelacion_resuelta']);

            $table->string('estadoAnterior', 20)->nullable();
            $table->string('estadoNuevo', 20)->nullable();
            $table->text('detalle')->nullable();

            $table->timestamp('created_at')->useCurrent();
            // Sin updated_at — el historial es inmutable.

            $table->index('idSancion', 'idx_histsancion_sancion');
            $table->index('idColegio', 'idx_histsancion_colegio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_sanciones');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reglamentos_torneo', function (Blueprint $table): void {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idReglamento');

            $table->unsignedBigInteger('idTorneo');
            $table->foreign('idTorneo')
                  ->references('idTorneo')->on('torneos')
                  ->onDelete('cascade');

            $table->string('nombreArchivo', 255);
            $table->string('rutaArchivo', 500);
            $table->bigInteger('tamanoBytes');
            $table->boolean('esActual')->default(true);

            $table->unsignedBigInteger('idUsuarioSubida');
            $table->foreign('idUsuarioSubida')
                  ->references('idUsuario')->on('usuarios')
                  ->onDelete('restrict');

            // Solo created_at, sin updated_at (registro inmutable)
            $table->timestamp('created_at')->useCurrent();

            $table->index(['idTorneo', 'esActual'], 'idx_reglamentos_torneo_actual');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reglamentos_torneo');
    }
};

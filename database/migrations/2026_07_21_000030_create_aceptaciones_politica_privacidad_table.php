<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aceptaciones_politica_privacidad', function (Blueprint $table) {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idAceptacion');
            $table->unsignedBigInteger('idUsuario');

            // 'na' para tipo=datos_sensibles: ese consentimiento no está
            // atado a una versión de la política general — es puntual sobre
            // los campos de salud (RH, EPS) y no cambia si la política
            // general se actualiza.
            $table->string('version', 20);
            $table->enum('tipo', ['politica_general', 'datos_sensibles']);
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('idUsuario')
                  ->references('idUsuario')->on('usuarios')
                  ->onDelete('cascade');

            $table->unique(['idUsuario', 'version', 'tipo'], 'uq_aceptacion_usuario_version_tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aceptaciones_politica_privacidad');
    }
};

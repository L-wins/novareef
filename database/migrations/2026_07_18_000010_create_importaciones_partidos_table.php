<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('importaciones_partidos', function (Blueprint $table) {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idImportacion');

            $table->unsignedBigInteger('idColegio');
            $table->foreign('idColegio')
                  ->references('idColegio')->on('colegios')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('idTorneo');
            $table->foreign('idTorneo')
                  ->references('idTorneo')->on('torneos')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('idUsuario');
            $table->foreign('idUsuario')
                  ->references('idUsuario')->on('usuarios')
                  ->onDelete('restrict');

            $table->string('nombreArchivoOriginal', 255);
            $table->unsignedBigInteger('idFormatoDefault');
            $table->foreign('idFormatoDefault')
                  ->references('idFormato')->on('formatos_designacion')
                  ->onDelete('restrict');

            // procesando: recién subido, filas en revisión.
            // confirmada: ya generó partidos (ver importacion_partidos_filas.idPartidoCreado).
            // revertida: se confirmó pero luego se deshizo (borró los partidos creados).
            // cancelada: el usuario abandonó el preview sin confirmar.
            $table->enum('estado', ['procesando', 'confirmada', 'revertida', 'cancelada'])
                  ->default('procesando');

            $table->unsignedSmallInteger('totalFilas')->default(0);
            $table->unsignedSmallInteger('totalCreados')->default(0);
            $table->timestamp('confirmadaEn')->nullable();
            $table->timestamp('revertidaEn')->nullable();

            $table->unsignedBigInteger('idUsuarioReversion')->nullable();
            $table->foreign('idUsuarioReversion')
                  ->references('idUsuario')->on('usuarios')
                  ->onDelete('set null');

            $table->timestamps();

            $table->index(['idColegio', 'estado'], 'idx_importaciones_colegio_estado');
            $table->index('idTorneo', 'idx_importaciones_torneo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('importaciones_partidos');
    }
};

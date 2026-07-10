<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencias_academicas', static function (Blueprint $table): void {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idAsistencia');

            $table->unsignedBigInteger('idColegio');
            $table->foreign('idColegio')
                  ->references('idColegio')->on('colegios')
                  ->onDelete('cascade');

            $table->unsignedBigInteger('idSesion');
            $table->foreign('idSesion')
                  ->references('idSesion')->on('sesiones_academicas')
                  ->onDelete('cascade');

            $table->unsignedBigInteger('idArbitro');
            $table->foreign('idArbitro')
                  ->references('idArbitro')->on('arbitros')
                  ->onDelete('restrict');

            $table->enum('estadoAsistencia', [
                'presente',
                'ausente',
                'justificacion_pendiente',
                'justificado',
                'justificacion_rechazada',
            ])->default('ausente');

            $table->dateTime('horaMarca')->nullable();
            $table->enum('registradoPor', ['arbitro', 'instructor', 'sistema'])->default('sistema');
            $table->boolean('confirmadoInstructor')->default(false);

            $table->timestamps();

            $table->unique(['idSesion', 'idArbitro'], 'uq_asistencia_sesion_arbitro');
            $table->index('idColegio',         'idx_asistencias_academicas_colegio');
            $table->index('estadoAsistencia',  'idx_asistencias_academicas_estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias_academicas');
    }
};

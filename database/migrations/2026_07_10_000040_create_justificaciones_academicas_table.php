<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('justificaciones_academicas', static function (Blueprint $table): void {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idJustificacion');

            $table->unsignedBigInteger('idColegio');
            $table->foreign('idColegio')
                  ->references('idColegio')->on('colegios')
                  ->onDelete('cascade');

            $table->unsignedBigInteger('idAsistencia');
            $table->foreign('idAsistencia')
                  ->references('idAsistencia')->on('asistencias_academicas')
                  ->onDelete('cascade');

            $table->unsignedBigInteger('idArbitro');
            $table->foreign('idArbitro')
                  ->references('idArbitro')->on('arbitros')
                  ->onDelete('restrict');

            $table->text('motivo');
            $table->string('documentoPdf', 255)->nullable();

            $table->enum('estadoJustificacion', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');
            $table->text('motivoRechazo')->nullable();

            $table->unsignedBigInteger('idUsuarioRevision')->nullable();
            $table->foreign('idUsuarioRevision')
                  ->references('idUsuario')->on('usuarios')
                  ->onDelete('set null');
            $table->dateTime('fechaRevision')->nullable();

            $table->date('fechaLimite');

            $table->timestamps();

            $table->unique('idAsistencia', 'uq_justificacion_asistencia');
            $table->index('idColegio',            'idx_justificaciones_academicas_colegio');
            $table->index('estadoJustificacion',  'idx_justificaciones_academicas_estado');
            $table->index('idArbitro',            'idx_justificaciones_academicas_arbitro');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('justificaciones_academicas');
    }
};

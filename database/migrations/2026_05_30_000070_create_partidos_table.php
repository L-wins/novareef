<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partidos', function (Blueprint $table) {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idPartido');

            $table->unsignedBigInteger('idTorneo');
            $table->foreign('idTorneo')
                  ->references('idTorneo')->on('torneos')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('idColegio');
            $table->foreign('idColegio')
                  ->references('idColegio')->on('colegios')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('idDivision');
            $table->foreign('idDivision')
                  ->references('idDivision')->on('divisiones_torneo')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('idSede')->nullable();
            $table->foreign('idSede')
                  ->references('idSede')->on('sedes_torneo')
                  ->onDelete('set null');

            $table->unsignedBigInteger('idFormato');
            $table->foreign('idFormato')
                  ->references('idFormato')->on('formatos_designacion')
                  ->onDelete('restrict');

            $table->string('equipoLocal', 150);
            $table->string('equipoVisitante', 150);
            $table->date('fechaPartido');
            $table->time('horaPartido');

            $table->enum('estadoPartido', ['programado', 'en_curso', 'finalizado', 'aplazado', 'cancelado'])
                  ->default('programado');

            $table->integer('resultadoLocal')->nullable();
            $table->integer('resultadoVisitante')->nullable();

            $table->enum('modalidadPago', ['campo', 'nomina'])->default('campo');
            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->index('idColegio',     'idx_partidos_colegio');
            $table->index('fechaPartido',  'idx_partidos_fecha');
            $table->index('estadoPartido', 'idx_partidos_estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partidos');
    }
};

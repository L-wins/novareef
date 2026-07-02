<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('torneos', function (Blueprint $table) {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idTorneo');

            $table->unsignedBigInteger('idColegio');
            $table->foreign('idColegio')
                  ->references('idColegio')->on('colegios')
                  ->onDelete('restrict');

            $table->string('nombreTorneo', 255);
            $table->enum('tipoTorneo', ['local', 'zonal', 'oficial'])->default('local');
            $table->enum('modalidadPago', ['campo', 'nomina'])->default('campo');
            $table->enum('estadoTorneo', ['proximo', 'activo', 'finalizado', 'cancelado'])->default('proximo');

            $table->string('organizadorNombre', 150);
            $table->string('organizadorTelefono', 20)->nullable();
            $table->string('organizadorEmail', 255)->nullable();

            $table->year('temporada');
            $table->date('fechaInicio');
            $table->date('fechaFin');

            $table->string('reglamentoPDF', 500)->nullable();

            $table->unsignedBigInteger('idUsuarioCreador');
            $table->foreign('idUsuarioCreador')
                  ->references('idUsuario')->on('usuarios')
                  ->onDelete('restrict');

            $table->timestamps();
            $table->softDeletes();

            $table->index('idColegio',    'idx_torneos_colegio');
            $table->index('estadoTorneo', 'idx_torneos_estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('torneos');
    }
};

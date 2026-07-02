<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indisponibilidades_extraordinarias', static function (Blueprint $table): void {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idIndisponibilidad');

            $table->unsignedBigInteger('idArbitro');
            $table->foreign('idArbitro')
                  ->references('idArbitro')->on('arbitros')
                  ->onDelete('cascade');

            $table->unsignedBigInteger('idColegio');
            $table->foreign('idColegio')
                  ->references('idColegio')->on('colegios')
                  ->onDelete('restrict');

            $table->date('fechaAfectada');

            $table->enum('franjaAfectada', [
                'am', 'pm', 'noche',
                'am_pm', 'am_noche', 'pm_noche',
                'todo_el_dia',
            ]);

            $table->text('motivo');

            $table->unsignedBigInteger('idUsuarioRegistro');
            $table->foreign('idUsuarioRegistro')
                  ->references('idUsuario')->on('usuarios')
                  ->onDelete('restrict');

            $table->timestamps();

            $table->index(['idArbitro', 'fechaAfectada'], 'idx_indisponibilidad_arbitro_fecha');
            $table->index('idColegio', 'idx_indisponibilidad_colegio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indisponibilidades_extraordinarias');
    }
};

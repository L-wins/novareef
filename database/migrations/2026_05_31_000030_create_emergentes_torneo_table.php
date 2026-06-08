<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergentes_torneo', function (Blueprint $table): void {
            $table->bigIncrements('idEmergente');

            $table->unsignedBigInteger('idTorneo');
            $table->unsignedBigInteger('idArbitro');
            $table->unsignedBigInteger('idSede');
            $table->date('fechaEmergente');
            $table->text('notas')->nullable();
            $table->unsignedBigInteger('idUsuarioAsignador');

            $table->timestamps();

            $table->foreign('idTorneo')->references('idTorneo')->on('torneos')->onDelete('restrict');
            $table->foreign('idArbitro')->references('idArbitro')->on('arbitros')->onDelete('restrict');
            $table->foreign('idSede')->references('idSede')->on('sedes_torneo')->onDelete('restrict');
            $table->foreign('idUsuarioAsignador')->references('idUsuario')->on('usuarios')->onDelete('restrict');

            $table->unique(['idTorneo', 'idArbitro', 'fechaEmergente'], 'uq_emergente_arbitro_fecha');
            $table->index('fechaEmergente');
            $table->index('idTorneo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergentes_torneo');
    }
};

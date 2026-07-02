<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_designaciones', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idHistorial');

            $table->unsignedBigInteger('idDesignacion')->nullable();
            $table->unsignedBigInteger('idPartido');
            $table->unsignedBigInteger('idArbitro')->nullable();
            $table->unsignedBigInteger('idColegio');
            $table->unsignedBigInteger('idUsuarioAccion')->nullable();

            $table->enum('tipoAccion', [
                'asignado',
                'confirmado',
                'rechazado',
                'quitado',
                'partido_creado',
                'estado_partido_cambiado',
                'emergente_cubrio',
            ]);

            $table->string('estadoAnterior', 50)->nullable();
            $table->string('estadoNuevo', 50)->nullable();
            $table->text('detalle')->nullable();

            $table->timestamp('created_at')->useCurrent();
            // Sin updated_at — el historial es inmutable

            $table->foreign('idDesignacion')->references('idDesignacion')->on('designaciones')->onDelete('set null');
            $table->foreign('idPartido')->references('idPartido')->on('partidos')->onDelete('restrict');
            $table->foreign('idArbitro')->references('idArbitro')->on('arbitros')->onDelete('set null');
            $table->foreign('idColegio')->references('idColegio')->on('colegios')->onDelete('restrict');
            $table->foreign('idUsuarioAccion')->references('idUsuario')->on('usuarios')->onDelete('set null');

            $table->index('idPartido', 'idx_historial_partido');
            $table->index('idColegio', 'idx_historial_colegio');
            $table->index('idArbitro', 'idx_historial_arbitro');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_designaciones');
    }
};

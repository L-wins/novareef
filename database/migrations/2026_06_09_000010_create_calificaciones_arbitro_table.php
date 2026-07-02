<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calificaciones_arbitro', function (Blueprint $table): void {
            $table->bigIncrements('idCalificacion');
            $table->unsignedBigInteger('idDesignacion')->comment('Una calificación por designación');
            $table->unsignedBigInteger('idVeedor');
            $table->unsignedBigInteger('idColegio');
            $table->decimal('nota', 2, 1);
            $table->text('comentario');
            $table->timestamps();

            $table->unique('idDesignacion');

            $table->foreign('idDesignacion')
                  ->references('idDesignacion')->on('designaciones')
                  ->onDelete('restrict');

            $table->foreign('idVeedor')
                  ->references('idUsuario')->on('usuarios')
                  ->onDelete('restrict');

            $table->foreign('idColegio')
                  ->references('idColegio')->on('colegios')
                  ->onDelete('restrict');

            $table->index('idColegio');
            $table->index('idVeedor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calificaciones_arbitro');
    }
};

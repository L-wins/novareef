<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designaciones', static function (Blueprint $table): void {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idDesignacion');

            $table->unsignedBigInteger('idPartido');
            $table->foreign('idPartido')
                  ->references('idPartido')->on('partidos')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('idArbitro');
            $table->foreign('idArbitro')
                  ->references('idArbitro')->on('arbitros')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('idRol');
            $table->foreign('idRol')
                  ->references('idRol')->on('roles_partido')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('idColegio');
            $table->foreign('idColegio')
                  ->references('idColegio')->on('colegios')
                  ->onDelete('restrict');

            $table->enum('estadoDesignacion', ['pendiente', 'confirmada', 'rechazada'])
                  ->default('pendiente');

            $table->text('motivoRechazo')->nullable();
            $table->timestamp('fechaConfirmacion')->nullable();
            $table->timestamp('fechaRechazo')->nullable();
            $table->boolean('notificacionEnviada')->default(false);
            $table->timestamp('fechaNotificacion')->nullable();

            $table->unsignedBigInteger('idUsuarioDesignador');
            $table->foreign('idUsuarioDesignador')
                  ->references('idUsuario')->on('usuarios')
                  ->onDelete('restrict');

            $table->timestamps();

            // Un árbitro no puede tener dos roles en el mismo partido
            $table->unique(['idPartido', 'idArbitro'], 'uq_designacion_partido_arbitro');

            $table->index('idColegio',          'idx_designaciones_colegio');
            $table->index('estadoDesignacion',   'idx_designaciones_estado');
            $table->index('idPartido',           'idx_designaciones_partido');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designaciones');
    }
};

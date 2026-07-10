<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesiones_academicas', static function (Blueprint $table): void {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idSesion');

            $table->unsignedBigInteger('idColegio');
            $table->foreign('idColegio')
                  ->references('idColegio')->on('colegios')
                  ->onDelete('cascade');

            $table->unsignedBigInteger('idInstructor');
            $table->foreign('idInstructor')
                  ->references('idUsuario')->on('usuarios')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('idTipoSesion');
            $table->foreign('idTipoSesion')
                  ->references('idTipoSesion')->on('tipos_sesion_academica')
                  ->onDelete('restrict');

            $table->enum('modalidad', ['presencial', 'virtual'])->default('presencial');
            $table->string('urlVirtual', 255)->nullable();

            $table->string('tema', 150);
            $table->text('descripcion')->nullable();
            $table->string('lugar', 150)->nullable();

            $table->date('fechaSesion');
            $table->time('horaSesion');
            $table->unsignedInteger('duracionMinutos');

            $table->enum('dirigidaA', ['todos', 'categoria'])->default('todos');
            $table->unsignedBigInteger('idCategoria')->nullable();
            $table->foreign('idCategoria')
                  ->references('idCategoria')->on('categorias_arbitro')
                  ->onDelete('set null');

            $table->enum('modoAsistencia', ['manual', 'scanner'])->default('manual');
            $table->enum('estadoSesion', ['programada', 'en_curso', 'finalizada', 'cancelada'])->default('programada');
            $table->boolean('sesionAbierta')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index('idColegio',     'idx_sesiones_academicas_colegio');
            $table->index('fechaSesion',   'idx_sesiones_academicas_fecha');
            $table->index('estadoSesion',  'idx_sesiones_academicas_estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesiones_academicas');
    }
};

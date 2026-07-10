<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_sesion_academica', static function (Blueprint $table): void {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idTipoSesion');

            $table->unsignedBigInteger('idColegio');
            $table->foreign('idColegio')
                  ->references('idColegio')->on('colegios')
                  ->onDelete('cascade');

            $table->string('nombre', 60);
            $table->string('etiqueta', 80);
            $table->boolean('esOficial')->default(false);
            $table->text('descripcion')->nullable();
            $table->boolean('esActivo')->default(true);
            $table->unsignedInteger('orden')->default(0);

            $table->timestamps();

            $table->unique(['idColegio', 'nombre'], 'uq_tipo_sesion_colegio_nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_sesion_academica');
    }
};

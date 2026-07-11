<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materiales_academicos', static function (Blueprint $table): void {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idMaterial');

            $table->unsignedBigInteger('idColegio');
            $table->foreign('idColegio')
                  ->references('idColegio')->on('colegios')
                  ->onDelete('cascade');

            $table->unsignedBigInteger('idSesion');
            $table->foreign('idSesion')
                  ->references('idSesion')->on('sesiones_academicas')
                  ->onDelete('cascade');

            $table->string('titulo', 150);
            $table->string('archivo', 255);
            $table->string('extension', 10);
            $table->unsignedBigInteger('tamanoBytes')->nullable();

            $table->unsignedBigInteger('idUsuarioSubio')->nullable();
            $table->foreign('idUsuarioSubio')
                  ->references('idUsuario')->on('usuarios')
                  ->onDelete('set null');

            $table->timestamps();

            $table->index('idSesion', 'idx_materiales_academicos_sesion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materiales_academicos');
    }
};

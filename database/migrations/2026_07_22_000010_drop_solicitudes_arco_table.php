<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('solicitudes_arco');
    }

    public function down(): void
    {
        Schema::create('solicitudes_arco', function (Blueprint $table) {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idSolicitud');
            $table->unsignedBigInteger('idUsuario');
            $table->unsignedBigInteger('idColegio');

            $table->enum('tipo', ['acceso', 'rectificacion', 'cancelacion', 'oposicion']);
            $table->text('mensaje');
            $table->enum('estado', ['pendiente', 'atendida'])->default('pendiente');

            $table->timestamps();

            $table->foreign('idUsuario')
                  ->references('idUsuario')->on('usuarios')
                  ->onDelete('cascade');

            $table->foreign('idColegio')
                  ->references('idColegio')->on('colegios')
                  ->onDelete('cascade');

            $table->index('idColegio', 'idx_solicitudes_arco_colegio');
        });
    }
};

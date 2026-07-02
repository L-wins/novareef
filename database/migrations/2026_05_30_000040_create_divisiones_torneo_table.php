<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('divisiones_torneo', function (Blueprint $table) {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idDivision');

            $table->unsignedBigInteger('idTorneo');
            $table->foreign('idTorneo')
                  ->references('idTorneo')->on('torneos')
                  ->onDelete('cascade');

            $table->string('nombreDivision', 100);
            $table->text('descripcion')->nullable();

            $table->timestamps();

            $table->unique(['idTorneo', 'nombreDivision'], 'uq_divisiones_torneo_nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('divisiones_torneo');
    }
};

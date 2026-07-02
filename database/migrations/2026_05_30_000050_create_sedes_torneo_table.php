<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sedes_torneo', function (Blueprint $table) {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idSede');

            $table->unsignedBigInteger('idTorneo');
            $table->foreign('idTorneo')
                  ->references('idTorneo')->on('torneos')
                  ->onDelete('cascade');

            $table->string('nombreSede', 150);
            $table->string('direccion', 255);
            $table->string('barrio', 100)->nullable();
            $table->string('municipio', 100);
            $table->text('observaciones')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sedes_torneo');
    }
};

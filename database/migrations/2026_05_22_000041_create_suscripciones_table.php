<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suscripciones', function (Blueprint $table): void {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idSuscripcion');
            $table->unsignedBigInteger('idColegio');
            $table->unsignedBigInteger('idPlan');
            $table->date('fechaInicio');
            $table->date('fechaVencimiento');
            $table->enum('estado', ['activa', 'vencida', 'suspendida', 'trial'])->default('trial');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->foreign('idColegio')->references('idColegio')->on('colegios')->restrictOnDelete();
            $table->foreign('idPlan')->references('idPlan')->on('planes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suscripciones');
    }
};

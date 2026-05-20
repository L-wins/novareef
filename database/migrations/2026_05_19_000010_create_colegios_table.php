<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colegios', function (Blueprint $table) {
            $table->engine  = 'InnoDB';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('idColegio');

            $table->string('tenantId')->unique();
            $table->string('nombreColegio');
            $table->string('codigoColegio', 20)->unique();
            $table->string('emailColegio');
            $table->string('telefonoColegio', 20)->nullable();
            $table->text('direccionColegio')->nullable();
            $table->string('ciudadColegio', 100)->nullable();
            $table->string('departamentoColegio', 100)->nullable();
            $table->string('paisColegio', 100)->default('Colombia');
            $table->string('logoColegio', 500)->nullable();
            $table->enum('estadoColegio', ['activo', 'prueba', 'suspendido'])->default('activo');
            $table->enum('planColegio', ['basico', 'profesional', 'enterprise'])->default('basico');
            $table->date('fechaSuscripcion')->nullable();
            $table->date('fechaExpiracion')->nullable();
            $table->timestamps();

            $table->foreign('tenantId')
                  ->references('id')
                  ->on('tenants')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colegios');
    }
};

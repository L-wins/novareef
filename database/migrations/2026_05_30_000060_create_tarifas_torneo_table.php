<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarifas_torneo', function (Blueprint $table) {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idTarifa');

            $table->unsignedBigInteger('idDivision');
            $table->foreign('idDivision')
                  ->references('idDivision')->on('divisiones_torneo')
                  ->onDelete('cascade');

            $table->unsignedBigInteger('idRol');
            $table->foreign('idRol')
                  ->references('idRol')->on('roles_partido')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('idFormato');
            $table->foreign('idFormato')
                  ->references('idFormato')->on('formatos_designacion')
                  ->onDelete('restrict');

            $table->decimal('valorPago', 12, 2)->default(0.00);

            $table->timestamps();

            $table->unique(['idDivision', 'idRol', 'idFormato'], 'uq_tarifas_division_rol_formato');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifas_torneo');
    }
};

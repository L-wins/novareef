<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arbitros', function (Blueprint $table) {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idArbitro');

            $table->unsignedBigInteger('idUsuario')->unique();
            $table->unsignedBigInteger('idColegio');
            $table->unsignedBigInteger('idCategoria');

            $table->string('numeroDocumento', 30);
            $table->enum('tipoDocumento', ['cedula', 'pasaporte', 'extranjeria'])
                  ->default('cedula');
            $table->string('lugarExpedicionCC', 100)->nullable();

            $table->decimal('pesoArbitro', 5, 2)->nullable();
            $table->decimal('estaturaArbitro', 4, 2)->nullable();
            $table->string('rhArbitro', 5)->nullable();
            $table->string('epsArbitro', 100)->nullable();
            $table->string('profesionArbitro', 100)->nullable();
            $table->date('fechaIngresoColegio')->nullable();

            $table->string('direccionArbitro', 255)->nullable();
            $table->string('barrioArbitro', 100)->nullable();

            $table->boolean('tieneVehiculo')->default(false);
            $table->enum('tipoVehiculo', ['carro', 'moto', 'ambos'])->nullable();
            $table->string('marcaVehiculo', 50)->nullable();
            $table->string('placaVehiculo', 20)->nullable();
            $table->string('colorVehiculo', 30)->nullable();

            $table->string('codigoCarnet', 20)->unique();

            $table->enum('estadoArbitro', [
                'activo',
                'inactivo',
                'suspendido',
                'retirado',
                'aprendiz',
                'proceso_ingreso',
            ])->default('proceso_ingreso');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('idUsuario')
                  ->references('idUsuario')
                  ->on('usuarios')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');

            $table->foreign('idColegio')
                  ->references('idColegio')
                  ->on('colegios')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->foreign('idCategoria')
                  ->references('idCategoria')
                  ->on('categorias_arbitro')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('arbitros', function (Blueprint $table) {
            $table->dropForeign(['idUsuario']);
            $table->dropForeign(['idColegio']);
            $table->dropForeign(['idCategoria']);
        });

        Schema::dropIfExists('arbitros');
    }
};

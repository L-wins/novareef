<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_arbitro', function (Blueprint $table) {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idCategoria');
            $table->unsignedBigInteger('idColegio');

            $table->string('nombreCategoria', 50);
            $table->text('descripcion')->nullable();
            $table->boolean('esPorDefecto')->default(false);
            $table->boolean('activa')->default(true);

            $table->timestamps();

            $table->unique(['idColegio', 'nombreCategoria']);

            $table->foreign('idColegio')
                  ->references('idColegio')
                  ->on('colegios')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('categorias_arbitro', function (Blueprint $table) {
            $table->dropForeign(['idColegio']);
        });

        Schema::dropIfExists('categorias_arbitro');
    }
};

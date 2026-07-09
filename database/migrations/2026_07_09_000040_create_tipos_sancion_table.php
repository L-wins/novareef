<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_sancion', static function (Blueprint $table): void {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idTipoSancion');

            $table->unsignedBigInteger('idColegio');
            $table->foreign('idColegio')
                  ->references('idColegio')->on('colegios')
                  ->onDelete('cascade');

            $table->string('nombre', 60);
            $table->string('etiqueta', 80);
            $table->enum('severidad', ['leve', 'moderada', 'grave'])->default('leve');
            $table->unsignedInteger('diasSuspensionSugeridos')->nullable();
            $table->text('descripcion')->nullable();
            $table->boolean('esActivo')->default(true);
            $table->unsignedInteger('orden')->default(0);

            $table->timestamps();

            $table->unique(['idColegio', 'nombre'], 'uq_tipo_sancion_colegio_nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_sancion');
    }
};

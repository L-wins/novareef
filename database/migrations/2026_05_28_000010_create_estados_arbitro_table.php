<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estados_arbitro', function (Blueprint $table) {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idEstado');
            $table->string('nombre', 30)->unique();
            $table->string('etiqueta', 50);
            $table->string('color', 20)->default('gray');
            $table->text('descripcion')->nullable();
            $table->boolean('permiteDesignar')->default(false);
            $table->boolean('esActivo')->default(true);
            $table->integer('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estados_arbitro');
    }
};

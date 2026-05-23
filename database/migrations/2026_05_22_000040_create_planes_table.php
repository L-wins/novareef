<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planes', function (Blueprint $table): void {
            $table->engine       = 'InnoDB';
            $table->charset      = 'utf8mb4';
            $table->collation    = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idPlan');
            $table->string('nombre', 50);
            $table->decimal('precio', 12, 2)->comment('COP');
            $table->enum('periodicidad', ['mensual', 'anual'])->default('mensual');
            $table->unsignedInteger('limiteArbitros')->nullable()->comment('NULL = ilimitado');
            $table->json('modulosJSON');
            $table->unsignedInteger('limiteRoles')->nullable()->comment('NULL = ilimitado');
            $table->boolean('incluyePaginaWeb')->default(false);
            $table->boolean('incluyeOnboarding')->default(false);
            $table->boolean('esVisible')->default(true);
            $table->boolean('esActivo')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planes');
    }
};

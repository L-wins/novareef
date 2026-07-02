<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slots_designacion', function (Blueprint $table): void {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idSlot');

            $table->unsignedBigInteger('idPartido');
            $table->unsignedBigInteger('idRol');

            // Para formatos con roles repetidos (Terna: Asistente slot 1 y slot 2)
            $table->integer('numeroSlot')->default(1);

            $table->unsignedBigInteger('idDesignacion')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('idPartido')->references('idPartido')->on('partidos')->onDelete('cascade');
            $table->foreign('idRol')->references('idRol')->on('roles_partido')->onDelete('restrict');
            $table->foreign('idDesignacion')->references('idDesignacion')->on('designaciones')->onDelete('set null');

            $table->unique(['idPartido', 'idRol', 'numeroSlot'], 'uq_slot_partido_rol_numero');
            $table->index('idPartido', 'idx_slots_partido');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slots_designacion');
    }
};

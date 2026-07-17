<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * arbitros.estadoArbitro se filtra constantemente sin índice propio
 * (ArbitroController::index, LimiteService::arbitrosActivos,
 * whereNotIn('estadoArbitro', ['retirado']) en varios lados,
 * candidatosParaPartido) — a diferencia del resto del esquema, que sí está
 * bien indexado en sus columnas de filtro frecuente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arbitros', static function (Blueprint $table): void {
            $table->index('estadoArbitro', 'idx_arbitros_estado');
        });
    }

    public function down(): void
    {
        Schema::table('arbitros', static function (Blueprint $table): void {
            $table->dropIndex('idx_arbitros_estado');
        });
    }
};

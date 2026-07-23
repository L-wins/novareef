<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Diagnóstico real (auditoría de carga, 800 árbitros sembrados en 6 colegios,
 * idColegio=10 con 200): EXPLAIN sobre la query de ArbitroController::index()
 * (WHERE arbitros.idColegio = ? AND arbitros.estadoArbitro = ? ... ORDER BY
 * usuarios.nombreUsuario) usaba solo arbitros_idcolegio_foreign, escaneaba
 * las 200 filas del colegio completo y hacía "Using temporary; Using
 * filesort" — el índice de estadoArbitro (2026_07_17_000020) existe pero
 * suelto no ayuda cuando el filtro combina ambas columnas. Con el índice
 * compuesto, MySQL resuelve idColegio+estadoArbitro en el mismo acceso.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arbitros', function (Blueprint $table) {
            $table->index(['idColegio', 'estadoArbitro'], 'idx_arbitros_colegio_estado');
        });
    }

    public function down(): void
    {
        Schema::table('arbitros', function (Blueprint $table) {
            $table->dropIndex('idx_arbitros_colegio_estado');
        });
    }
};

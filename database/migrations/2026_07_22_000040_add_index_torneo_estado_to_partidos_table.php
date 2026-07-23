<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Diagnóstico real (auditoría de carga, colegio idColegio=10 con 200
 * partidos): EXPLAIN sobre ReporteDesignacionesService::listadoPartidosDeTorneo()
 * (WHERE idColegio = ? AND idTorneo = ? AND estadoPartido = ? ORDER BY
 * fechaPartido) solo usaba idx_partidos_colegio — "Using where; Using
 * filesort" sobre las 200 filas del colegio. idx_partidos_estado existe pero
 * MySQL no lo eligió para esta combinación de filtros; un índice compuesto
 * (idTorneo, estadoPartido, fechaPartido) resuelve filtro + orden en un
 * único acceso para el listado paginado de partidos de un torneo, que es la
 * pantalla más visitada de Designaciones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partidos', function (Blueprint $table) {
            $table->index(['idTorneo', 'estadoPartido', 'fechaPartido'], 'idx_partidos_torneo_estado_fecha');
        });
    }

    public function down(): void
    {
        Schema::table('partidos', function (Blueprint $table) {
            $table->dropIndex('idx_partidos_torneo_estado_fecha');
        });
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emergentes_torneo', static function (Blueprint $table): void {
            if (!Schema::hasColumn('emergentes_torneo', 'idColegio')) {
                $table->unsignedBigInteger('idColegio')
                      ->nullable()
                      ->after('idSede');

                $table->foreign('idColegio')
                      ->references('idColegio')->on('colegios')
                      ->onDelete('restrict');
            }

            // Índice compuesto (idTorneo, fechaEmergente) para búsquedas por torneo+fecha
            $table->index(['idTorneo', 'fechaEmergente'], 'idx_emergentes_torneo_fecha');
            $table->index('idColegio', 'idx_emergentes_colegio');
        });

        // Rellenar idColegio en registros existentes a partir del torneo
        DB::statement("
            UPDATE `emergentes_torneo` et
            INNER JOIN `torneos` t ON t.`idTorneo` = et.`idTorneo`
            SET et.`idColegio` = t.`idColegio`
            WHERE et.`idColegio` IS NULL
        ");

        // Hacer la columna NOT NULL una vez poblada
        Schema::table('emergentes_torneo', static function (Blueprint $table): void {
            $table->unsignedBigInteger('idColegio')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('emergentes_torneo', static function (Blueprint $table): void {
            $table->dropIndex('idx_emergentes_torneo_fecha');
            $table->dropIndex('idx_emergentes_colegio');

            if (Schema::hasColumn('emergentes_torneo', 'idColegio')) {
                $table->dropForeign(['idColegio']);
                $table->dropColumn('idColegio');
            }
        });
    }
};

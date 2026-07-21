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

            // idx_emergentes_colegio quedó como el único índice que respalda
            // fk_idColegio (se crearon en la misma sentencia ALTER en up(),
            // sin índice implícito propio para la FK) — hay que soltar la FK
            // antes de poder soltar el índice, o MySQL rechaza el DROP INDEX
            // (error 1553), igual que en idx_arbitros_estado.
            if (Schema::hasColumn('emergentes_torneo', 'idColegio')) {
                $table->dropForeign(['idColegio']);
            }

            $table->dropIndex('idx_emergentes_colegio');

            if (Schema::hasColumn('emergentes_torneo', 'idColegio')) {
                $table->dropColumn('idColegio');
            }
        });
    }
};

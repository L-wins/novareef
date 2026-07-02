<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Eliminar columnas de resultado (se gestiona en designaciones)
        Schema::table('partidos', static function ($table): void {
            $table->dropColumn(['resultadoLocal', 'resultadoVisitante']);
        });

        // 2. Reemplazar ENUM estadoPartido con los estados del M04
        DB::statement("
            ALTER TABLE `partidos`
            MODIFY COLUMN `estadoPartido`
                ENUM(
                    'programado',
                    'en_curso',
                    'confirmado',
                    'critico',
                    'aplazado',
                    'cancelado',
                    'finalizado'
                )
                NOT NULL
                DEFAULT 'programado'
        ");
    }

    public function down(): void
    {
        // Restaurar ENUM original (valores que estaban fuera del nuevo set → 'programado')
        DB::statement("
            UPDATE `partidos`
            SET `estadoPartido` = 'programado'
            WHERE `estadoPartido` IN ('confirmado', 'critico')
        ");

        DB::statement("
            ALTER TABLE `partidos`
            MODIFY COLUMN `estadoPartido`
                ENUM('programado','en_curso','finalizado','aplazado','cancelado')
                NOT NULL
                DEFAULT 'programado'
        ");

        Schema::table('partidos', static function ($table): void {
            $table->integer('resultadoLocal')->nullable()->after('estadoPartido');
            $table->integer('resultadoVisitante')->nullable()->after('resultadoLocal');
        });
    }
};

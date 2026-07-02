<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar 'borrador' como estado inicial del ciclo de vida del partido.
        // Los partidos en borrador no son visibles para los árbitros hasta publicarse.
        DB::statement("
            ALTER TABLE `partidos`
            MODIFY COLUMN `estadoPartido`
                ENUM(
                    'borrador',
                    'programado',
                    'confirmado',
                    'en_curso',
                    'finalizado',
                    'critico',
                    'aplazado',
                    'cancelado'
                )
                NOT NULL
                DEFAULT 'borrador'
        ");
    }

    public function down(): void
    {
        // Los borradores no existen en el ENUM anterior → se promueven a programado
        DB::statement("
            UPDATE `partidos`
            SET `estadoPartido` = 'programado'
            WHERE `estadoPartido` = 'borrador'
        ");

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
};

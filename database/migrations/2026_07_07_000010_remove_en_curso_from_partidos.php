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
        // No debería haber filas en 'en_curso' (el estado ya no se usa desde
        // la capa de aplicación), pero por seguridad se reclasifican antes de
        // que el ENUM deje de aceptar ese valor.
        DB::table('partidos')->where('estadoPartido', 'en_curso')->update(['estadoPartido' => 'confirmado']);

        DB::statement("
            ALTER TABLE `partidos`
            MODIFY COLUMN `estadoPartido`
                ENUM(
                    'borrador',
                    'programado',
                    'confirmado',
                    'critico',
                    'aplazado',
                    'cancelado',
                    'finalizado'
                )
                NOT NULL
                DEFAULT 'borrador'
        ");

        // horaInicio solo alimentaba la finalización automática 150 min después
        // de pasar a 'en_curso' — sin ese estado, la columna queda sin uso.
        Schema::table('partidos', function (Blueprint $table): void {
            $table->dropColumn('horaInicio');
        });
    }

    public function down(): void
    {
        Schema::table('partidos', function (Blueprint $table): void {
            $table->timestamp('horaInicio')->nullable()->after('idVeedor');
        });

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
};

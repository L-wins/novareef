<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 'saldo_inicial' — capitalización de apertura del colegio (o ajuste
        // posterior de caja), no ingreso operativo. Se abona en el mismo
        // instante en que se registra (ver FinanzasService::registrarSaldoInicial).
        DB::statement("
            ALTER TABLE `movimientos_financieros`
            MODIFY COLUMN `categoria`
                ENUM(
                    'ingreso_torneo',
                    'mensualidad',
                    'multa',
                    'otro_ingreso',
                    'saldo_inicial',
                    'nomina_arbitro',
                    'arbitro_externo',
                    'gasto_fijo',
                    'gasto_institucional',
                    'gasto_vario'
                )
                NOT NULL
        ");
    }

    public function down(): void
    {
        // historial_movimientos_financieros tiene FK restrict a idMovimiento —
        // hay que limpiarlo antes de poder borrar el movimiento (abonos_movimiento
        // sí es cascade, no hace falta tocarlo aparte).
        DB::statement("
            DELETE h FROM `historial_movimientos_financieros` h
            INNER JOIN `movimientos_financieros` m ON m.idMovimiento = h.idMovimiento
            WHERE m.categoria = 'saldo_inicial'
        ");

        DB::statement("
            DELETE FROM `movimientos_financieros` WHERE `categoria` = 'saldo_inicial'
        ");

        DB::statement("
            ALTER TABLE `movimientos_financieros`
            MODIFY COLUMN `categoria`
                ENUM(
                    'ingreso_torneo',
                    'mensualidad',
                    'multa',
                    'otro_ingreso',
                    'nomina_arbitro',
                    'arbitro_externo',
                    'gasto_fijo',
                    'gasto_institucional',
                    'gasto_vario'
                )
                NOT NULL
        ");
    }
};

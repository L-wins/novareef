<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * MySQL rechaza (trunca/error en modo strict) cualquier UPDATE que
     * escriba un valor fuera del ENUM vigente — así que no se puede mapear
     * los datos antes de que el enum acepte los valores nuevos. Por eso el
     * orden real es: 1) ensanchar el enum para que acepte los 5 valores
     * viejos + los 3 nuevos a la vez, 2) mapear los datos, 3) angostar el
     * enum a los 3 valores finales.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE abonos_movimiento MODIFY metodoPago ENUM('efectivo', 'transferencia', 'consignacion', 'compensacion_nomina', 'otro', 'pago_digital', 'nomina') NOT NULL");

        DB::table('abonos_movimiento')
            ->whereIn('metodoPago', ['transferencia', 'consignacion', 'otro'])
            ->update(['metodoPago' => 'pago_digital']);

        DB::table('abonos_movimiento')
            ->where('metodoPago', 'compensacion_nomina')
            ->update(['metodoPago' => 'nomina']);

        // Laravel/DBAL no editan un ENUM de MySQL limpiamente con ->change().
        DB::statement("ALTER TABLE abonos_movimiento MODIFY metodoPago ENUM('efectivo', 'pago_digital', 'nomina') NOT NULL");
    }

    /**
     * Reversión con pérdida de precisión documentada: una vez colapsados
     * transferencia/consignacion/otro en pago_digital, no hay forma de
     * recuperar cuál era cuál — down() solo restaura la forma del schema
     * (todo lo que era pago_digital vuelve como 'transferencia', mejor
     * esfuerzo, no exacto).
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE abonos_movimiento MODIFY metodoPago ENUM('efectivo', 'transferencia', 'consignacion', 'compensacion_nomina', 'otro') NOT NULL");

        DB::table('abonos_movimiento')->where('metodoPago', 'pago_digital')->update(['metodoPago' => 'transferencia']);
        DB::table('abonos_movimiento')->where('metodoPago', 'nomina')->update(['metodoPago' => 'compensacion_nomina']);
    }
};

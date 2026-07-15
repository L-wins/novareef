<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos_financieros', static function (Blueprint $table): void {
            // Agrupa los movimientos creados en una misma corrida de cobro
            // masivo (M06) — mismo tipo/tamaño que idLotePago en
            // abonos_movimiento, pero es un concepto distinto: éste agrupa
            // movimientos recién creados (pendientes o ya saldados), no abonos.
            $table->char('idLoteCobro', 36)->nullable()->after('idOrigenMulta');
            $table->index('idLoteCobro', 'idx_movfin_lote_cobro');
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_financieros', static function (Blueprint $table): void {
            $table->dropIndex('idx_movfin_lote_cobro');
            $table->dropColumn('idLoteCobro');
        });
    }
};

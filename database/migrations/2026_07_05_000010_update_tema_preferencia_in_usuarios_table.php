<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Evoluciona temaPreferencia de ENUM('oscuro','claro') a VARCHAR(10)
     * con valores canónicos light | dark | system (mapean 1:1 a data-theme).
     * Default 'dark': preserva el look actual para usuarios existentes.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE usuarios MODIFY temaPreferencia VARCHAR(10) NOT NULL DEFAULT 'dark'");

        DB::table('usuarios')->where('temaPreferencia', 'oscuro')->update(['temaPreferencia' => 'dark']);
        DB::table('usuarios')->where('temaPreferencia', 'claro')->update(['temaPreferencia' => 'light']);
    }

    public function down(): void
    {
        DB::table('usuarios')->where('temaPreferencia', 'dark')->update(['temaPreferencia' => 'oscuro']);
        DB::table('usuarios')->whereIn('temaPreferencia', ['light', 'system'])->update(['temaPreferencia' => 'claro']);

        DB::statement("ALTER TABLE usuarios MODIFY temaPreferencia ENUM('oscuro','claro') NOT NULL DEFAULT 'oscuro'");
    }
};

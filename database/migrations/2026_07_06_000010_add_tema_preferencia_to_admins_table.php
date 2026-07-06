<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Preferencia de tema del superadmin (light | dark | system).
     * Mismo formato que usuarios.temaPreferencia, default 'dark'
     * para preservar el look actual del panel admin.
     */
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table): void {
            $table->string('temaPreferencia', 10)->default('dark')->after('activo');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table): void {
            $table->dropColumn('temaPreferencia');
        });
    }
};

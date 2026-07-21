<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abonos_movimiento', function (Blueprint $table): void {
            $table->dropColumn('referencia');
        });
    }

    /**
     * Reversión con pérdida de datos: el contenido original de la columna no
     * se recupera, solo se restaura la forma del schema.
     */
    public function down(): void
    {
        Schema::table('abonos_movimiento', function (Blueprint $table): void {
            $table->string('referencia', 100)->nullable();
        });
    }
};

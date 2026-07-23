<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trazabilidad al reglamento interno/estatutos: cada tipo de sanción puede
 * referenciar el artículo/cláusula que sanciona — sin esto no había forma de
 * justificar una sanción contra el documento normativo del colegio si algún
 * día se cuestiona.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_sancion', static function (Blueprint $table): void {
            $table->string('articuloReglamento', 120)->nullable()->after('etiqueta');
        });
    }

    public function down(): void
    {
        Schema::table('tipos_sancion', static function (Blueprint $table): void {
            $table->dropColumn('articuloReglamento');
        });
    }
};

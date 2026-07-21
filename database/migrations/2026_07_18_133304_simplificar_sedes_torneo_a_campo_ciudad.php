<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Simplifica la sede de torneo: direccion + municipio + departamento
 * (3 campos) se reemplazan por un único campo `ciudad`, que además es el
 * que se exporta tal cual en el listado PDF de designaciones (columna
 * CIUDAD) — ya no depende de que el municipio matchee nada, siempre existe
 * porque es obligatorio al crear la sede. Los datos de `municipio` ya
 * cargados se preservan copiándolos a `ciudad` antes de eliminar las
 * columnas viejas (dirección física exacta y departamento se descartan a
 * propósito — decisión de producto: la sede queda identificada por nombre +
 * ciudad + urlMaps, no por dirección postal completa).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sedes_torneo', function (Blueprint $table): void {
            $table->string('ciudad', 100)->nullable()->after('nombreSede');
        });

        DB::table('sedes_torneo')->update(['ciudad' => DB::raw('municipio')]);

        Schema::table('sedes_torneo', function (Blueprint $table): void {
            $table->string('ciudad', 100)->nullable(false)->change();
            $table->dropColumn(['direccion', 'municipio', 'departamento']);
        });
    }

    public function down(): void
    {
        Schema::table('sedes_torneo', function (Blueprint $table): void {
            $table->string('direccion', 255)->default('')->after('nombreSede');
            $table->string('municipio', 100)->default('')->after('direccion');
            $table->string('departamento', 100)->nullable()->after('municipio');
        });

        DB::table('sedes_torneo')->update(['municipio' => DB::raw('ciudad')]);

        Schema::table('sedes_torneo', function (Blueprint $table): void {
            $table->dropColumn('ciudad');
        });
    }
};

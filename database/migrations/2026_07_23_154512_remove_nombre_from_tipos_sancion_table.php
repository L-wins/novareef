<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El "nombre interno" del tipo de sanción sobraba: la etiqueta visible es el
 * único identificador que el usuario necesita — mismo criterio ya aplicado en
 * tipos_sesion_academica (ver 2026_07_10_000050_remove_nombre_from_tipos_sesion_academica.php).
 * La unicidad por colegio pasa de (idColegio, nombre) a (idColegio, etiqueta).
 *
 * Orden importante: el unique viejo es el índice que respalda el FK de
 * idColegio — hay que crear el unique nuevo ANTES de soltar el viejo para
 * que el FK nunca quede sin índice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_sancion', static function (Blueprint $table): void {
            $table->unique(['idColegio', 'etiqueta'], 'uq_tipo_sancion_colegio_etiqueta');
        });

        Schema::table('tipos_sancion', static function (Blueprint $table): void {
            $table->dropUnique('uq_tipo_sancion_colegio_nombre');
            $table->dropColumn('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('tipos_sancion', static function (Blueprint $table): void {
            $table->string('nombre', 60)->after('idColegio')->default('');
            $table->unique(['idColegio', 'nombre'], 'uq_tipo_sancion_colegio_nombre');
        });

        Schema::table('tipos_sancion', static function (Blueprint $table): void {
            $table->dropUnique('uq_tipo_sancion_colegio_etiqueta');
        });
    }
};

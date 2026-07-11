<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El "nombre interno" del tipo de sesión sobraba: la etiqueta visible es el
 * único identificador que el usuario necesita. La unicidad por colegio pasa
 * de (idColegio, nombre) a (idColegio, etiqueta).
 *
 * Orden importante: el unique viejo es el índice que respalda el FK de
 * idColegio (MySQL lo reutilizó al crear la tabla) — hay que crear el unique
 * nuevo ANTES de soltar el viejo para que el FK nunca quede sin índice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_sesion_academica', static function (Blueprint $table): void {
            $table->unique(['idColegio', 'etiqueta'], 'uq_tipo_sesion_colegio_etiqueta');
        });

        Schema::table('tipos_sesion_academica', static function (Blueprint $table): void {
            $table->dropUnique('uq_tipo_sesion_colegio_nombre');
            $table->dropColumn('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('tipos_sesion_academica', static function (Blueprint $table): void {
            $table->string('nombre', 60)->after('idColegio')->default('');
            $table->unique(['idColegio', 'nombre'], 'uq_tipo_sesion_colegio_nombre');
        });

        Schema::table('tipos_sesion_academica', static function (Blueprint $table): void {
            $table->dropUnique('uq_tipo_sesion_colegio_etiqueta');
        });
    }
};

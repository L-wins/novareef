<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * fechaInicioSancion era obligatoria incluso para sanciones puramente
 * económicas (pagar una multa sin ningún rango de suspensión) — el Comité
 * terminaba inventando una fecha de "inicio" que no significaba nada. Ahora
 * NULL representa "sin suspensión" (mismo criterio que fechaFinSancion NULL
 * ya representa "suspensión indefinida"): con ambas nulas, la sanción es
 * solo un registro + multa opcional; con solo fechaInicioSancion, es
 * suspensión indefinida; con las dos, suspensión con rango definido.
 *
 * DB::statement en vez de ->nullable()->change() porque el proyecto no tiene
 * doctrine/dbal instalado (requerido por Blueprint::change() en Laravel 11).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE sanciones MODIFY fechaInicioSancion DATE NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE sanciones MODIFY fechaInicioSancion DATE NOT NULL');
    }
};

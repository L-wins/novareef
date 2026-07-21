<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * arbitros.estadoArbitro se filtra constantemente sin índice propio
 * (ArbitroController::index, LimiteService::arbitrosActivos,
 * whereNotIn('estadoArbitro', ['retirado']) en varios lados,
 * candidatosParaPartido) — a diferencia del resto del esquema, que sí está
 * bien indexado en sus columnas de filtro frecuente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arbitros', static function (Blueprint $table): void {
            $table->index('estadoArbitro', 'idx_arbitros_estado');
        });
    }

    /**
     * La FK fk_arbitros_estado (migración 2026_05_28_000030) se crea sin
     * índice propio en esa fecha — MySQL le genera uno implícito. Cuando
     * esta migración corre después y agrega idx_arbitros_estado sobre la
     * misma columna, MySQL detecta el índice implícito como redundante y
     * lo consolida en este: idx_arbitros_estado termina siendo el ÚNICO
     * índice que respalda esa FK. Mientras la FK exista, MySQL rechaza el
     * DROP INDEX (error 1553 "needed in a foreign key constraint") sin
     * importar el orden del rollback — verificado reproduciendo el error
     * con migrate:fresh + migrate:rollback sobre una base limpia. Si la FK
     * ya fue revertida (rollback dirigido solo a esta migración, después
     * de la de 05_28), es seguro quitar el índice.
     */
    public function down(): void
    {
        $fkExiste = DB::table('information_schema.table_constraints')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'arbitros')
            ->where('constraint_name', 'fk_arbitros_estado')
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();

        if ($fkExiste) {
            return;
        }

        Schema::table('arbitros', static function (Blueprint $table): void {
            $table->dropIndex('idx_arbitros_estado');
        });
    }
};

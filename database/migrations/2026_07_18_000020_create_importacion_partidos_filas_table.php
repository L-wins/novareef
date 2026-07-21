<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('importacion_partidos_filas', function (Blueprint $table) {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idFila');

            $table->unsignedBigInteger('idImportacion');
            $table->foreign('idImportacion')
                  ->references('idImportacion')->on('importaciones_partidos')
                  ->onDelete('cascade');

            // Identificador estable de la fila dentro de la importación —
            // el formulario de preview lo usa como clave del array editado
            // (filas[clave][...]), igual que antes con el UUID en sesión.
            $table->string('clave', 40);

            // ── Texto crudo del Word (auditoría — nunca se sobreescribe) ──
            $table->string('grupoTexto', 255)->nullable();
            $table->string('categoriaTexto', 255)->default('');
            $table->string('fechaTexto', 255)->default('');
            $table->string('asociacionTexto', 255)->nullable();
            $table->string('nombreSedeTexto', 255)->default('');
            $table->string('diaTexto', 255)->default('');
            $table->string('ciudadTexto', 255)->default('');

            // Roles crudos (ARBITRO/LINEA UNO/LINEA DOS/EMERGENTE) tal como
            // vinieron: nombre + asociación de esa fila puntual — cualquiera
            // de los 4 puede pertenecer a una asociación distinta al colegio
            // que importa, no solo el emergente.
            $table->json('rolesTexto')->nullable();

            // ── Valores editables (parten del parseo, el usuario los corrige) ──
            $table->string('equipoLocal', 255)->default('');
            $table->string('equipoVisitante', 255)->default('');
            $table->date('fechaPartido')->nullable();
            $table->time('horaPartido')->nullable();

            $table->unsignedBigInteger('idDivisionMatch')->nullable();
            $table->foreign('idDivisionMatch')
                  ->references('idDivision')->on('divisiones_torneo')
                  ->onDelete('set null');

            $table->unsignedBigInteger('idSedeMatch')->nullable();
            $table->foreign('idSedeMatch')
                  ->references('idSede')->on('sedes_torneo')
                  ->onDelete('set null');

            $table->unsignedBigInteger('idFormato')->nullable();
            $table->foreign('idFormato')
                  ->references('idFormato')->on('formatos_designacion')
                  ->onDelete('set null');

            // Matching de árbitros por rol ya resuelto: [{idRol, nombreTexto,
            // asociacionTexto, idArbitroMatch, mismoColegio, sugerencia}, ...]
            // Se resuelve una vez en el preview inicial; el usuario puede
            // corregir manualmente cada rol antes de confirmar.
            $table->json('designacionesMatch')->nullable();

            $table->boolean('incluir')->default(true);
            $table->boolean('esPosibleDuplicado')->default(false);

            $table->json('errores')->nullable();
            $table->json('advertencias')->nullable();

            // Se llena solo al confirmar — permite revertir (borrar solo los
            // partidos que ESTA importación creó) sin adivinar por fecha/equipo.
            $table->unsignedBigInteger('idPartidoCreado')->nullable();
            $table->foreign('idPartidoCreado')
                  ->references('idPartido')->on('partidos')
                  ->onDelete('set null');

            $table->timestamps();

            $table->unique(['idImportacion', 'clave'], 'uq_importacion_fila_clave');
            $table->index('idImportacion', 'idx_filas_importacion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('importacion_partidos_filas');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisitos_documento_arbitro', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idRequisito');
            $table->unsignedBigInteger('idColegio');
            $table->unsignedBigInteger('idCategoria')->nullable();
            $table->string('nombre', 120);
            $table->text('descripcion')->nullable();
            $table->boolean('obligatorio')->default(true);
            $table->boolean('requiereRevision')->default(true);
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->string('plantillaRuta', 500)->nullable();
            $table->string('plantillaNombreOriginal', 180)->nullable();
            $table->string('plantillaMime', 120)->nullable();
            $table->bigInteger('plantillaTamanoBytes')->nullable();
            $table->timestamps();

            $table->foreign('idColegio')
                ->references('idColegio')
                ->on('colegios')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('idCategoria')
                ->references('idCategoria')
                ->on('categorias_arbitro')
                ->onUpdate('cascade')
                ->onDelete('set null');

            $table->unique(['idColegio', 'nombre'], 'uq_req_doc_arbitro_colegio_nombre');
            $table->index(['idColegio', 'activo', 'orden'], 'idx_req_doc_arbitro_colegio_activo');
            $table->index(['idColegio', 'idCategoria', 'activo', 'orden'], 'idx_req_doc_arbitro_categoria');
        });

        Schema::table('documentos_arbitro', function (Blueprint $table) {
            $table->unsignedBigInteger('idRequisito')->nullable()->after('idArbitro');
            $table->string('nombreOriginal', 180)->nullable()->after('nombreDocumento');
            $table->string('estadoRevision', 30)->default('pendiente')->after('fechaSubida');
            $table->text('comentarioRevision')->nullable()->after('estadoRevision');
            $table->timestamp('fechaRevision')->nullable()->after('comentarioRevision');
            $table->unsignedBigInteger('idUsuarioRevision')->nullable()->after('fechaRevision');
            $table->unsignedInteger('version')->default(1)->after('idUsuarioRevision');

            $table->foreign('idRequisito')
                ->references('idRequisito')
                ->on('requisitos_documento_arbitro')
                ->onUpdate('cascade')
                ->onDelete('set null');

            $table->foreign('idUsuarioRevision')
                ->references('idUsuario')
                ->on('usuarios')
                ->onUpdate('cascade')
                ->onDelete('set null');

            $table->index(['idArbitro', 'idRequisito', 'estadoRevision'], 'idx_doc_arbitro_revision');
        });
    }

    public function down(): void
    {
        Schema::table('documentos_arbitro', function (Blueprint $table) {
            $table->dropForeign(['idRequisito']);
            $table->dropForeign(['idUsuarioRevision']);
            $table->dropIndex('idx_doc_arbitro_revision');
            $table->dropColumn([
                'idRequisito',
                'nombreOriginal',
                'estadoRevision',
                'comentarioRevision',
                'fechaRevision',
                'idUsuarioRevision',
                'version',
            ]);
        });

        Schema::dropIfExists('requisitos_documento_arbitro');
    }
};

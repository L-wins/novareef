<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requisitos_documento_arbitro', function (Blueprint $table) {
            $table->unsignedBigInteger('idCategoria')->nullable()->after('idColegio');

            $table->foreign('idCategoria')
                ->references('idCategoria')
                ->on('categorias_arbitro')
                ->onUpdate('cascade')
                ->onDelete('set null');

            $table->index(['idColegio', 'idCategoria', 'activo', 'orden'], 'idx_req_doc_arbitro_categoria');
        });
    }

    public function down(): void
    {
        Schema::table('requisitos_documento_arbitro', function (Blueprint $table) {
            $table->dropForeign(['idCategoria']);
            $table->dropIndex('idx_req_doc_arbitro_categoria');
            $table->dropColumn('idCategoria');
        });
    }
};

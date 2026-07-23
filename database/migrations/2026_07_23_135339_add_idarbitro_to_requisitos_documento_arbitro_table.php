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
            $table->unsignedBigInteger('idArbitro')->nullable()->after('idCategoria');

            $table->foreign('idArbitro')
                ->references('idArbitro')
                ->on('arbitros')
                ->onUpdate('cascade')
                ->onDelete('set null');

            $table->index(['idColegio', 'idArbitro', 'activo', 'orden'], 'idx_req_doc_arbitro_arbitro');
        });
    }

    public function down(): void
    {
        Schema::table('requisitos_documento_arbitro', function (Blueprint $table) {
            $table->dropForeign(['idArbitro']);
            $table->dropIndex('idx_req_doc_arbitro_arbitro');
            $table->dropColumn('idArbitro');
        });
    }
};

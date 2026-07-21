<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partidos', function (Blueprint $table) {
            $table->unsignedBigInteger('idImportacion')->nullable()->after('idFormato');
            $table->foreign('idImportacion')
                  ->references('idImportacion')->on('importaciones_partidos')
                  ->onDelete('set null');

            $table->index('idImportacion', 'idx_partidos_importacion');
        });
    }

    public function down(): void
    {
        Schema::table('partidos', function (Blueprint $table) {
            $table->dropForeign(['idImportacion']);
            $table->dropIndex('idx_partidos_importacion');
            $table->dropColumn('idImportacion');
        });
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_arbitro', function (Blueprint $table) {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idDocumento');
            $table->unsignedBigInteger('idArbitro');

            $table->string('nombreDocumento', 100);
            $table->string('archivoRuta', 500);
            $table->string('tipoMime', 100)->nullable();
            $table->bigInteger('tamanoBytes')->nullable();
            $table->boolean('obligatorio')->default(false);
            $table->timestamp('fechaSubida')->useCurrent();

            $table->timestamps();

            $table->foreign('idArbitro')
                  ->references('idArbitro')
                  ->on('arbitros')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('documentos_arbitro', function (Blueprint $table) {
            $table->dropForeign(['idArbitro']);
        });

        Schema::dropIfExists('documentos_arbitro');
    }
};

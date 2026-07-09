<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sanciones', static function (Blueprint $table): void {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idSancion');

            $table->unsignedBigInteger('idColegio');
            $table->foreign('idColegio')
                  ->references('idColegio')->on('colegios')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('idArbitro');
            $table->foreign('idArbitro')
                  ->references('idArbitro')->on('arbitros')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('idTipoSancion');
            $table->foreign('idTipoSancion')
                  ->references('idTipoSancion')->on('tipos_sancion')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('idPartido')->nullable();
            $table->foreign('idPartido')
                  ->references('idPartido')->on('partidos')
                  ->onDelete('set null');

            $table->text('motivoSancion');
            $table->date('fechaHecho');
            $table->date('fechaInicioSancion');
            $table->date('fechaFinSancion')->nullable();

            $table->enum('estadoSancion', ['activa', 'cumplida', 'anulada', 'apelada'])
                  ->default('activa');

            $table->boolean('tieneMultaEconomica')->default(false);

            $table->unsignedBigInteger('idMovimientoFinanciero')->nullable();
            $table->foreign('idMovimientoFinanciero')
                  ->references('idMovimiento')->on('movimientos_financieros')
                  ->onDelete('set null');

            $table->unsignedBigInteger('idUsuarioImpuso');
            $table->foreign('idUsuarioImpuso')
                  ->references('idUsuario')->on('usuarios')
                  ->onDelete('restrict');

            $table->unsignedInteger('version')->default(0);

            $table->timestamps();

            $table->index('idColegio',      'idx_sanciones_colegio');
            $table->index('idArbitro',      'idx_sanciones_arbitro');
            $table->index('estadoSancion',  'idx_sanciones_estado');
            $table->index('fechaFinSancion', 'idx_sanciones_fecha_fin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sanciones');
    }
};

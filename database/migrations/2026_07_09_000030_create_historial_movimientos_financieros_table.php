<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_movimientos_financieros', static function (Blueprint $table): void {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idHistorial');

            $table->unsignedBigInteger('idMovimiento');
            $table->foreign('idMovimiento')
                  ->references('idMovimiento')->on('movimientos_financieros')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('idColegio');
            $table->foreign('idColegio')
                  ->references('idColegio')->on('colegios')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('idUsuarioAccion')->nullable();
            $table->foreign('idUsuarioAccion')
                  ->references('idUsuario')->on('usuarios')
                  ->onDelete('set null');

            $table->enum('tipoAccion', ['creado', 'abonado', 'anulado', 'compensado']);

            $table->string('estadoAnterior', 20)->nullable();
            $table->string('estadoNuevo', 20)->nullable();
            $table->text('detalle')->nullable();

            $table->timestamp('created_at')->useCurrent();
            // Sin updated_at — el historial es inmutable.

            $table->index('idMovimiento', 'idx_histmovfin_movimiento');
            $table->index('idColegio',    'idx_histmovfin_colegio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_movimientos_financieros');
    }
};

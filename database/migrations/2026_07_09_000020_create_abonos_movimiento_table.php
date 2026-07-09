<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abonos_movimiento', static function (Blueprint $table): void {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idAbono');

            $table->unsignedBigInteger('idMovimiento');
            $table->foreign('idMovimiento')
                  ->references('idMovimiento')->on('movimientos_financieros')
                  ->onDelete('cascade');

            $table->unsignedBigInteger('idColegio');
            $table->foreign('idColegio')
                  ->references('idColegio')->on('colegios')
                  ->onDelete('restrict');

            $table->decimal('monto', 12, 2);
            $table->date('fechaAbono');
            $table->enum('metodoPago', ['efectivo', 'transferencia', 'consignacion', 'compensacion_nomina', 'otro']);
            $table->string('referencia', 100)->nullable();

            // Agrupa abonos creados en una misma operación de pago acumulado
            // (ej. pago de nómina + compensación de deudas del mismo lote).
            $table->char('idLotePago', 36)->nullable();

            $table->boolean('anulado')->default(false);

            $table->unsignedBigInteger('idUsuarioRegistro');
            $table->foreign('idUsuarioRegistro')
                  ->references('idUsuario')->on('usuarios')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('idUsuarioAnulacion')->nullable();
            $table->foreign('idUsuarioAnulacion')
                  ->references('idUsuario')->on('usuarios')
                  ->onDelete('set null');

            $table->dateTime('fechaAnulacion')->nullable();
            $table->text('observaciones')->nullable();

            $table->timestamp('created_at')->useCurrent();
            // Sin updated_at — los abonos son registros de auditoría inmutables.

            $table->index('idMovimiento', 'idx_abono_movimiento');
            $table->index('idColegio',    'idx_abono_colegio');
            $table->index('idLotePago',   'idx_abono_lote');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abonos_movimiento');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_financieros', static function (Blueprint $table): void {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idMovimiento');

            $table->unsignedBigInteger('idColegio');
            $table->foreign('idColegio')
                  ->references('idColegio')->on('colegios')
                  ->onDelete('restrict');

            $table->enum('tipoMovimiento', ['ingreso', 'egreso']);

            $table->enum('categoria', [
                // Ingreso
                'ingreso_torneo',
                'mensualidad',
                'multa',
                'otro_ingreso',
                // Egreso
                'nomina_arbitro',
                'arbitro_externo',
                'gasto_fijo',
                'gasto_institucional',
                'gasto_vario',
            ]);

            $table->string('concepto', 255);
            $table->decimal('montoTotal', 12, 2);
            $table->enum('estadoMovimiento', ['pendiente', 'parcial', 'pagado', 'anulado'])
                  ->default('pendiente');
            $table->date('fechaMovimiento');

            $table->unsignedBigInteger('idArbitro')->nullable();
            $table->foreign('idArbitro')
                  ->references('idArbitro')->on('arbitros')
                  ->onDelete('restrict');

            $table->string('nombreArbitroExterno', 150)->nullable();
            $table->string('documentoArbitroExterno', 30)->nullable();

            $table->unsignedBigInteger('idTorneo')->nullable();
            $table->foreign('idTorneo')
                  ->references('idTorneo')->on('torneos')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('idPartido')->nullable();
            $table->foreign('idPartido')
                  ->references('idPartido')->on('partidos')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('idDesignacion')->nullable();
            $table->foreign('idDesignacion')
                  ->references('idDesignacion')->on('designaciones')
                  ->onDelete('set null');

            // Origen desacoplado de multas — sin FK: el origen puede ser
            // 'sancion' (tabla sanciones, M07), 'academico' (tabla futura de
            // M05, aún no existe) o 'manual' (sin origen formal).
            $table->enum('tipoOrigenMulta', ['sancion', 'academico', 'manual'])->nullable();
            $table->unsignedBigInteger('idOrigenMulta')->nullable();

            // Nullable: los movimientos generados automáticamente al finalizar un
            // partido en modalidad nómina (GenerarPagosJob) no tienen un usuario
            // detrás — mismo criterio que idUsuarioAccion en HistorialDesignacion
            // para transiciones de estado disparadas por jobs (ver VerificarConfirmacionesJob).
            $table->unsignedBigInteger('idUsuarioRegistro')->nullable();
            $table->foreign('idUsuarioRegistro')
                  ->references('idUsuario')->on('usuarios')
                  ->onDelete('set null');

            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->index('idColegio',        'idx_movfin_colegio');
            $table->index('tipoMovimiento',   'idx_movfin_tipo');
            $table->index('categoria',        'idx_movfin_categoria');
            $table->index('estadoMovimiento', 'idx_movfin_estado');
            $table->index('fechaMovimiento',  'idx_movfin_fecha');
            $table->index('idArbitro',        'idx_movfin_arbitro');
            $table->index('idTorneo',         'idx_movfin_torneo');
            $table->index('idPartido',        'idx_movfin_partido');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_financieros');
    }
};

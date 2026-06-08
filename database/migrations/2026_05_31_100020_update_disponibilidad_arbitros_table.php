<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('disponibilidad_arbitros')) {
            // La tabla ya existe de una versión anterior: adaptar al nuevo esquema
            Schema::table('disponibilidad_arbitros', static function (Blueprint $table): void {
                if (Schema::hasColumn('disponibilidad_arbitros', 'estaDisponible')) {
                    $table->dropColumn('estaDisponible');
                }
                if (Schema::hasColumn('disponibilidad_arbitros', 'motivoNoDisponibilidad')) {
                    $table->dropColumn('motivoNoDisponibilidad');
                }
                if (!Schema::hasColumn('disponibilidad_arbitros', 'franjaHoraria')) {
                    $table->enum('franjaHoraria', [
                        'am', 'pm', 'noche',
                        'am_pm', 'am_noche', 'pm_noche',
                        'todo_el_dia',
                    ])->notNull()->after('fechaDisponibilidad');
                }
                if (!Schema::hasColumn('disponibilidad_arbitros', 'motivo')) {
                    $table->text('motivo')->nullable()->after('franjaHoraria');
                }
            });
            return;
        }

        // La tabla no existía → crearla directamente con el esquema final
        Schema::create('disponibilidad_arbitros', static function (Blueprint $table): void {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idDisponibilidad');

            $table->unsignedBigInteger('idArbitro');
            $table->foreign('idArbitro')
                  ->references('idArbitro')->on('arbitros')
                  ->onDelete('cascade');

            $table->date('fechaDisponibilidad');

            $table->enum('franjaHoraria', [
                'am', 'pm', 'noche',
                'am_pm', 'am_noche', 'pm_noche',
                'todo_el_dia',
            ]);

            $table->text('motivo')->nullable();

            $table->timestamps();

            // Un árbitro tiene exactamente un registro de disponibilidad por fecha
            $table->unique(['idArbitro', 'fechaDisponibilidad'], 'uq_disponibilidad_arbitro_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disponibilidad_arbitros');
    }
};

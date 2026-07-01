<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partidos', function (Blueprint $table): void {
            $table->unsignedBigInteger('idVeedor')->nullable()->after('observaciones');
            $table->timestamp('horaInicio')->nullable()->after('idVeedor');

            $table->foreign('idVeedor')
                  ->references('idUsuario')->on('usuarios')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('partidos', function (Blueprint $table): void {
            $table->dropForeign(['idVeedor']);
            $table->dropColumn(['idVeedor', 'horaInicio']);
        });
    }
};

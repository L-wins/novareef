<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('torneos', function (Blueprint $table): void {
            $table->decimal('valorEmergente', 12, 2)->nullable()->default(null)->after('fechaFin');
        });
    }

    public function down(): void
    {
        Schema::table('torneos', function (Blueprint $table): void {
            $table->dropColumn('valorEmergente');
        });
    }
};

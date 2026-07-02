<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sedes_torneo', function (Blueprint $table): void {
            $table->string('departamento', 100)->nullable()->after('municipio');
        });
    }

    public function down(): void
    {
        Schema::table('sedes_torneo', function (Blueprint $table): void {
            $table->dropColumn('departamento');
        });
    }
};

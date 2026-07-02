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
            $table->unsignedBigInteger('version')->default(0)->after('estadoPartido');
        });
    }

    public function down(): void
    {
        Schema::table('partidos', function (Blueprint $table): void {
            $table->dropColumn('version');
        });
    }
};

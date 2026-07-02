<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colegios', function (Blueprint $table): void {
            $table->dropColumn('planColegio');
        });
    }

    public function down(): void
    {
        Schema::table('colegios', function (Blueprint $table): void {
            $table->enum('planColegio', ['basico', 'profesional', 'enterprise'])
                  ->default('basico')
                  ->after('estadoColegio');
        });
    }
};

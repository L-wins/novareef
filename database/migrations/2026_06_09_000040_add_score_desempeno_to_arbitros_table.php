<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arbitros', function (Blueprint $table): void {
            $table->decimal('scoreDesempeno', 3, 2)->nullable()->after('estadoArbitro');
        });
    }

    public function down(): void
    {
        Schema::table('arbitros', function (Blueprint $table): void {
            $table->dropColumn('scoreDesempeno');
        });
    }
};

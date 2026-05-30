<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arbitros', function (Blueprint $table) {
            $table->string('fotoPerfil', 500)->nullable()->after('colorVehiculo');
        });
    }

    public function down(): void
    {
        Schema::table('arbitros', function (Blueprint $table) {
            $table->dropColumn('fotoPerfil');
        });
    }
};

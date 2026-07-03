<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE planes CHANGE limiteRoles limiteCuentasAdmin INT(10) UNSIGNED NULL COMMENT 'NULL = ilimitado'"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE planes CHANGE limiteCuentasAdmin limiteRoles INT(10) UNSIGNED NULL COMMENT 'NULL = ilimitado'"
        );
    }
};

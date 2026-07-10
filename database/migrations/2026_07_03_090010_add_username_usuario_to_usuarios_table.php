<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // emailUsuario nullable: un índice UNIQUE en MySQL permite múltiples NULL,
        // así que las cuentas admin internas (sin email propio) pueden dejarlo vacío
        // sin romper la unicidad de las cuentas que sí lo usan para login.
        DB::statement('ALTER TABLE usuarios MODIFY COLUMN emailUsuario VARCHAR(255) NULL');

        DB::statement(
            "ALTER TABLE usuarios ADD COLUMN usernameUsuario VARCHAR(60) NULL UNIQUE AFTER emailUsuario"
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE usuarios DROP COLUMN usernameUsuario');
        DB::statement('ALTER TABLE usuarios MODIFY COLUMN emailUsuario VARCHAR(255) NOT NULL');
    }
};

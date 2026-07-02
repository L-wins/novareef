<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE usuarios MODIFY COLUMN rolUsuario ENUM(
            'arbitro','ejecutivo','tesorero','designador',
            'sanciones','tecnico','veedor','superadmin'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE usuarios MODIFY COLUMN rolUsuario ENUM(
            'arbitro','ejecutivo','tesorero','designador',
            'sanciones','tecnico','superadmin'
        ) NOT NULL");
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE disponibilidad_arbitros MODIFY COLUMN franjaHoraria ENUM(
            'am', 'pm', 'noche',
            'am_pm', 'am_noche', 'pm_noche',
            'todo_el_dia', 'no_disponible'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE disponibilidad_arbitros MODIFY COLUMN franjaHoraria ENUM(
            'am', 'pm', 'noche',
            'am_pm', 'am_noche', 'pm_noche',
            'todo_el_dia'
        ) NOT NULL");
    }
};

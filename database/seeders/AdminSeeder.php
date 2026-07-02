<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::updateOrCreate(
            ['email' => 'admin@novareef.com'],
            [
                'nombre'             => 'SuperAdmin NovaReef',
                'password'           => Hash::make('NovaReef2026!'),
                'two_factor_enabled' => false,
                'activo'             => true,
            ]
        );
    }
}

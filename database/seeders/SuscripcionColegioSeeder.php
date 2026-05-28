<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Colegio;
use App\Models\Plan;
use App\Models\Suscripcion;
use Illuminate\Database\Seeder;

class SuscripcionColegioSeeder extends Seeder
{
    public function run(): void
    {
        $colegio = Colegio::find(1);

        if (! $colegio) {
            return;
        }

        $tieneSuscripcionActiva = Suscripcion::where('idColegio', 1)
            ->where('estado', 'activa')
            ->exists();

        if ($tieneSuscripcionActiva) {
            return;
        }

        $plan = Plan::where('nombre', 'Zenith')->first()
            ?? Plan::where('esActivo', true)->orderBy('orden')->first();

        if (! $plan) {
            return;
        }

        Suscripcion::create([
            'idColegio'        => 1,
            'idPlan'           => $plan->idPlan,
            'fechaInicio'      => today(),
            'fechaVencimiento' => today()->addYear(),
            'estado'           => 'activa',
        ]);
    }
}

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CLAVE = 'horas_limite_confirmacion';

    public function up(): void
    {
        // configuracion_colegio es key-value: se inserta la clave para los
        // colegios existentes. Los nuevos la reciben via ConfiguracionColegioSeeder.
        $ahora = now();

        $colegios = DB::table('colegios')->pluck('idColegio');

        foreach ($colegios as $idColegio) {
            $existe = DB::table('configuracion_colegio')
                ->where('idColegio', $idColegio)
                ->where('clave', self::CLAVE)
                ->exists();

            if (! $existe) {
                DB::table('configuracion_colegio')->insert([
                    'idColegio'   => $idColegio,
                    'clave'       => self::CLAVE,
                    'valor'       => '4',
                    'descripcion' => 'Horas que tiene el árbitro para confirmar una designación antes de que el partido pase a CRÍTICO',
                    'created_at'  => $ahora,
                    'updated_at'  => $ahora,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('configuracion_colegio')->where('clave', self::CLAVE)->delete();
    }
};

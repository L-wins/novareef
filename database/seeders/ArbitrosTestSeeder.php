<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Arbitro;
use App\Models\CategoriaArbitro;
use App\Models\Colegio;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class ArbitrosTestSeeder extends Seeder
{
    private const ARBITROS_POR_COLEGIO = 20;

    private const RH       = ['O+', 'A+', 'B+', 'AB+', 'O-'];
    private const EPS      = ['Sura', 'Sanitas', 'Nueva EPS', 'Compensar', 'Famisanar'];
    private const PROFESION = ['Docente', 'Contador', 'Ingeniero', 'Administrador', 'Técnico'];
    private const BARRIO   = ['Chapinero', 'Usaquén', 'Kennedy', 'Suba', 'Fontibón'];
    private const MARCA_VEHICULO = ['Chevrolet', 'Yamaha', 'Honda', 'Mazda'];
    private const COLOR_VEHICULO = ['Blanco', 'Negro', 'Rojo', 'Azul', 'Gris'];
    private const TIPO_VEHICULO  = ['carro', 'moto', 'ambos'];

    // 75% activos, 25% proceso_ingreso
    private const ESTADOS = ['activo', 'activo', 'activo', 'proceso_ingreso'];

    public function run(): void
    {
        $passwordHash = Hash::make('password');
        $anio         = now()->year;

        foreach (Colegio::all() as $colegio) {
            $categorias = CategoriaArbitro::where('idColegio', $colegio->idColegio)
                ->where('activa', true)
                ->orderBy('idCategoria')
                ->get();

            if ($categorias->isEmpty()) {
                $this->command->warn("Colegio {$colegio->codigoColegio} no tiene categorías activas — omitido.");
                continue;
            }

            // Prefijo legible: primeras 3 letras del código, en minúsculas para email
            $prefijo      = strtoupper(substr($colegio->codigoColegio, 0, 3));
            $prefijoEmail = strtolower($prefijo);

            $this->command->info("Seeding {$colegio->codigoColegio} ({$prefijo})...");

            for ($n = 1; $n <= self::ARBITROS_POR_COLEGIO; $n++) {
                $email = "arbitro{$n}.{$prefijoEmail}@test.com";

                // ── Usuario ──────────────
                $usuario = User::withTrashed()->updateOrCreate(
                    ['emailUsuario' => $email],
                    [
                        'idColegio'          => $colegio->idColegio,
                        'nombreUsuario'      => "Árbitro {$n} {$prefijo}",
                        'passwordUsuario'    => $passwordHash,
                        'telefonoUsuario'    => '300' . str_pad((string) $n, 7, '0', STR_PAD_LEFT),
                        'rolUsuario'         => 'arbitro',
                        'estadoUsuario'      => 'activo',
                        'must_change_password' => false,
                    ]
                );

                // Asignar rol Spatie (idempotente — assignRole ignora duplicados)
                if (! $usuario->hasRole('arbitro')) {
                    $usuario->assignRole('arbitro');
                }

                // ── Árbitro ───────────────
                $tieneVehiculo = ($n % 2 === 0);
                $categoria     = $categorias->get(($n - 1) % $categorias->count());

                // Generar codigoCarnet solo si el árbitro aún no existe
                $arbitroExistente = Arbitro::withTrashed()
                    ->where('idUsuario', $usuario->idUsuario)
                    ->first();

                if ($arbitroExistente === null) {
                    $secuencial    = Arbitro::withTrashed()
                        ->where('idColegio', $colegio->idColegio)
                        ->where('codigoCarnet', 'like', "NR-{$colegio->idColegio}-{$anio}-%")
                        ->count() + 1;
                    $codigoCarnet = "NR-{$colegio->idColegio}-{$anio}-" . str_pad((string) $secuencial, 4, '0', STR_PAD_LEFT);
                } else {
                    $codigoCarnet = $arbitroExistente->codigoCarnet;
                }

                Arbitro::updateOrCreate(
                    ['idUsuario' => $usuario->idUsuario],
                    [
                        'idColegio'          => $colegio->idColegio,
                        'idCategoria'        => $categoria->idCategoria,
                        'numeroDocumento'    => '100000' . str_pad((string) $n, 4, '0', STR_PAD_LEFT) . $colegio->idColegio,
                        'tipoDocumento'      => 'cedula',
                        'lugarExpedicionCC'  => 'Bogotá',
                        'pesoArbitro'        => round(mt_rand(6500, 8500) / 100, 2),
                        'estaturaArbitro'    => round(mt_rand(165, 185) / 100, 2),
                        'rhArbitro'          => self::RH[($n - 1) % count(self::RH)],
                        'epsArbitro'         => self::EPS[($n - 1) % count(self::EPS)],
                        'profesionArbitro'   => self::PROFESION[($n - 1) % count(self::PROFESION)],
                        'fechaIngresoColegio' => Carbon::createFromTimestamp(
                            mt_rand(
                                Carbon::parse('2020-01-01')->timestamp,
                                Carbon::parse('2024-12-31')->timestamp,
                            )
                        )->toDateString(),
                        'direccionArbitro'   => "Calle " . ($n * 2) . " # {$n}-" . ($n * 3),
                        'barrioArbitro'      => self::BARRIO[($n - 1) % count(self::BARRIO)],
                        'tieneVehiculo'      => $tieneVehiculo,
                        'tipoVehiculo'       => $tieneVehiculo ? self::TIPO_VEHICULO[($n - 1) % count(self::TIPO_VEHICULO)] : null,
                        'marcaVehiculo'      => $tieneVehiculo ? self::MARCA_VEHICULO[($n - 1) % count(self::MARCA_VEHICULO)] : null,
                        'placaVehiculo'      => $tieneVehiculo ? 'ABC' . str_pad((string) $n, 3, '0', STR_PAD_LEFT) : null,
                        'colorVehiculo'      => $tieneVehiculo ? self::COLOR_VEHICULO[($n - 1) % count(self::COLOR_VEHICULO)] : null,
                        'codigoCarnet'       => $codigoCarnet,
                        'estadoArbitro'      => self::ESTADOS[($n - 1) % count(self::ESTADOS)],
                    ]
                );
            }

            $this->command->info("  ✓ " . self::ARBITROS_POR_COLEGIO . " árbitros creados para {$colegio->codigoColegio}.");
        }
    }
}

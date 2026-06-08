<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\CategoriaArbitro;
use App\Models\Colegio;
use App\Models\ConfiguracionColegio;
use App\Models\Plan;
use App\Models\Suscripcion;
use Illuminate\Support\Facades\DB;

final class RegistrarColegio
{
    /** Categorías que se crean automáticamente en todo colegio nuevo. */
    private const CATEGORIAS_DEFAULT = ['FIFA', 'A', 'A-FEM', 'B', 'C'];

    public function __construct(
        private readonly RegistrarUsuarioConCredenciales $registrarUsuario,
    ) {}

    /**
     * Crea el colegio, su suscripción, categorías por defecto, configuración
     * inicial y el usuario ejecutivo con credenciales. Todo en una transacción.
     *
     * El correo de credenciales se despacha vía DB::afterCommit() dentro de
     * RegistrarUsuarioConCredenciales, por lo que nunca se envía en rollback.
     *
     * @throws \InvalidArgumentException  Si el plan o el rol no existen.
     * @throws \RuntimeException          Si el email del admin ya está en uso.
     */
    public function ejecutar(
        string $nombreColegio,
        string $codigoColegio,
        string $emailColegio,
        ?string $telefonoColegio,
        ?string $direccionColegio,
        ?string $ciudadColegio,
        ?string $departamentoColegio,
        string $paisColegio,
        ?string $logoColegio,
        int    $idPlan,
        string $nombreAdmin,
        string $emailAdmin,
    ): Colegio {
        return DB::transaction(function () use (
            $nombreColegio, $codigoColegio, $emailColegio,
            $telefonoColegio, $direccionColegio, $ciudadColegio,
            $departamentoColegio, $paisColegio, $logoColegio,
            $idPlan, $nombreAdmin, $emailAdmin,
        ): Colegio {
            $plan     = Plan::findOrFail($idPlan);
            $tenantId = $this->buildTenantId($codigoColegio);

            DB::table('tenants')->insertOrIgnore([
                'id'         => $tenantId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $colegio = Colegio::create([
                'tenantId'            => $tenantId,
                'nombreColegio'       => $nombreColegio,
                'codigoColegio'       => $codigoColegio,
                'emailColegio'        => $emailColegio,
                'telefonoColegio'     => $telefonoColegio,
                'direccionColegio'    => $direccionColegio,
                'ciudadColegio'       => $ciudadColegio,
                'departamentoColegio' => $departamentoColegio,
                'paisColegio'         => $paisColegio,
                'logoColegio'         => $logoColegio,
            ]);

            $this->crearSuscripcion($colegio, $plan);
            $this->crearCategoriasDefault($colegio->idColegio);
            $this->crearConfiguracionInicial($colegio->idColegio);

            $urlAcceso = 'https://' . $tenantId . '.novareef.com';

            $this->registrarUsuario->ejecutar(
                idColegio:    $colegio->idColegio,
                nombre:       $nombreAdmin,
                email:        $emailAdmin,
                telefono:     '',
                rol:          'ejecutivo',
                nombreColegio: $nombreColegio,
                urlAcceso:    $urlAcceso,
            );

            return $colegio;
        });
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

    private function crearSuscripcion(Colegio $colegio, Plan $plan): void
    {
        $inicio = today();

        Suscripcion::create([
            'idColegio'        => $colegio->idColegio,
            'idPlan'           => $plan->idPlan,
            'fechaInicio'      => $inicio,
            'fechaVencimiento' => $plan->calcularVencimiento($inicio),
            'estado'           => 'activa',
        ]);
    }

    private function crearCategoriasDefault(int $idColegio): void
    {
        $categorias = array_map(
            fn (string $nombre) => [
                'idColegio'       => $idColegio,
                'nombreCategoria' => $nombre,
                'esPorDefecto'    => true,
                'activa'          => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            self::CATEGORIAS_DEFAULT,
        );

        CategoriaArbitro::insert($categorias);
    }

    private function crearConfiguracionInicial(int $idColegio): void
    {
        ConfiguracionColegio::firstOrCreate(
            ['idColegio' => $idColegio, 'clave' => 'dia_disponibilidad'],
            ['valor'     => '1'],
        );
    }

    /**
     * Convierte el código del colegio en un tenant ID válido (solo alfanumérico y guiones).
     * Ejemplo: "CAC-001" → "cac-001"
     */
    private function buildTenantId(string $codigo): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($codigo)));
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Arbitro;
use App\Models\CategoriaArbitro;
use App\Models\Colegio;
use App\Models\ConfiguracionColegio;
use App\Models\Plan;
use App\Models\Suscripcion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ColegioService
{
    /** Categorías que se crean automáticamente en todo colegio nuevo. */
    private const CATEGORIAS_DEFAULT = ['FIFA', 'A', 'A-FEM', 'B', 'C'];

    /** Duración de la prueba gratuita opcional al crear un colegio. */
    public const DIAS_PRUEBA_GRATUITA = 7;

    /**
     * Estados válidos del ciclo de vida de un colegio — coincide exactamente
     * con el ENUM de la columna `colegios.estadoColegio` en BD. No agregar un
     * estado aquí sin antes migrar la columna.
     */
    private const ESTADOS_VALIDOS = ['activo', 'suspendido'];

    /** Etiqueta legible por estado, usada en mensajes flash tras un cambio. */
    private const ESTADO_LABELS = [
        'activo' => 'activado',
        'suspendido' => 'suspendido',
    ];

    public function __construct(
        private readonly ArbitroService $arbitros,
    ) {}

    /**
     * Crea el colegio, su suscripción, categorías por defecto, configuración
     * inicial y el usuario ejecutivo con credenciales. Todo en una transacción.
     *
     * El correo de credenciales se despacha vía DB::afterCommit() dentro de
     * ArbitroService::registrarConCredenciales(), por lo que nunca se envía en rollback.
     *
     * $idPlan es obligatorio salvo en prueba gratuita: ahí el colegio no
     * elige un plan comercial — se le asigna automáticamente el de mayor
     * jerarquía (todos los módulos, límites ilimitados) para que pueda
     * evaluar la plataforma completa durante los DIAS_PRUEBA_GRATUITA.
     *
     * @throws \InvalidArgumentException Si el plan o el rol no existen.
     * @throws \RuntimeException Si el email del admin ya está en uso.
     */
    public function registrar(
        string $nombreColegio,
        string $codigoColegio,
        string $emailColegio,
        ?string $telefonoColegio,
        ?string $direccionColegio,
        ?string $ciudadColegio,
        ?string $departamentoColegio,
        string $paisColegio,
        ?string $logoColegio,
        ?int $idPlan,
        string $nombreAdmin,
        string $emailAdmin,
        bool $iniciarComoTrial = false,
    ): Colegio {
        return DB::transaction(function () use (
            $nombreColegio, $codigoColegio, $emailColegio,
            $telefonoColegio, $direccionColegio, $ciudadColegio,
            $departamentoColegio, $paisColegio, $logoColegio,
            $idPlan, $nombreAdmin, $emailAdmin, $iniciarComoTrial,
        ): Colegio {
            $plan = $iniciarComoTrial && $idPlan === null
                ? $this->planParaPrueba()
                : Plan::findOrFail($idPlan);

            $tenantId = $this->buildTenantId($codigoColegio);

            DB::table('tenants')->insertOrIgnore([
                'id' => $tenantId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $colegio = Colegio::create([
                'tenantId' => $tenantId,
                'nombreColegio' => $nombreColegio,
                'codigoColegio' => $codigoColegio,
                'emailColegio' => $emailColegio,
                'telefonoColegio' => $telefonoColegio,
                'direccionColegio' => $direccionColegio,
                'ciudadColegio' => $ciudadColegio,
                'departamentoColegio' => $departamentoColegio,
                'paisColegio' => $paisColegio,
                'logoColegio' => $logoColegio,
            ]);

            $this->crearSuscripcion($colegio, $plan, $iniciarComoTrial);
            $this->crearCategoriasDefault($colegio->idColegio);
            $this->crearConfiguracionInicial($colegio->idColegio);

            // Sin subdominio real por colegio todavía (ver TenancyServiceProvider,
            // no registrado) — misma URL de acceso que usan ArbitroController y
            // CuentaAdminController al invitar cuentas nuevas.
            $urlAcceso = config('app.url').'/login';

            $this->arbitros->registrarConCredenciales(
                idColegio: $colegio->idColegio,
                nombre: $nombreAdmin,
                email: $emailAdmin,
                telefono: '',
                rol: 'ejecutivo',
                nombreColegio: $nombreColegio,
                urlAcceso: $urlAcceso,
            );

            return $colegio;
        });
    }

    /**
     * Usuario ejecutivo más reciente del colegio — quien administra la cuenta.
     */
    public function adminPrincipal(Colegio $colegio): ?User
    {
        return $colegio->usuarios()
            ->where('rolUsuario', 'ejecutivo')
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Conteo de árbitros del colegio por estado, en una sola query
     * con sumas condicionales en lugar de tres queries separadas.
     *
     * @return array{total: int, activos: int, enProceso: int}
     */
    public function estadisticasArbitros(int $idColegio): array
    {
        $stats = Arbitro::where('idColegio', $idColegio)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(estadoArbitro = 'activo') as activos")
            ->selectRaw("SUM(estadoArbitro = 'proceso_ingreso') as en_proceso")
            ->first();

        return [
            'total' => (int) $stats->total,
            'activos' => (int) $stats->activos,
            'enProceso' => (int) $stats->en_proceso,
        ];
    }

    /**
     * Cambia el estado del colegio. Si el estado solicitado no es válido,
     * alterna entre activo/suspendido (comportamiento del botón rápido del panel).
     *
     * @return string Etiqueta legible del estado final, para el mensaje flash.
     */
    public function cambiarEstado(Colegio $colegio, ?string $estadoSolicitado): string
    {
        $estadoFinal = in_array($estadoSolicitado, self::ESTADOS_VALIDOS, true)
            ? $estadoSolicitado
            : ($colegio->estadoColegio === 'activo' ? 'suspendido' : 'activo');

        $colegio->update(['estadoColegio' => $estadoFinal]);

        return self::ESTADO_LABELS[$estadoFinal];
    }

    // ── Helpers privados ──────────────────

    private function crearSuscripcion(Colegio $colegio, Plan $plan, bool $iniciarComoTrial): void
    {
        $inicio = today();

        Suscripcion::create([
            'idColegio' => $colegio->idColegio,
            'idPlan' => $plan->idPlan,
            'fechaInicio' => $inicio,
            'fechaVencimiento' => $iniciarComoTrial
                ? $inicio->copy()->addDays(self::DIAS_PRUEBA_GRATUITA)
                : $plan->calcularVencimiento($inicio),
            'estado' => $iniciarComoTrial ? 'trial' : 'activa',
            'notas' => $iniciarComoTrial
                ? 'Prueba gratuita de '.self::DIAS_PRUEBA_GRATUITA.' días desde el panel admin.'
                : null,
        ]);
    }

    private function crearCategoriasDefault(int $idColegio): void
    {
        $categorias = array_map(
            fn (string $nombre) => [
                'idColegio' => $idColegio,
                'nombreCategoria' => $nombre,
                'esPorDefecto' => true,
                'activa' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            self::CATEGORIAS_DEFAULT,
        );

        CategoriaArbitro::insert($categorias);
    }

    private function crearConfiguracionInicial(int $idColegio): void
    {
        ConfiguracionColegio::firstOrCreate(
            ['idColegio' => $idColegio, 'clave' => 'dia_disponibilidad'],
            ['valor' => '1'],
        );
    }

    /**
     * Plan de mayor jerarquía disponible — el que se asigna por debajo sin
     * que el superadmin lo elija cuando el colegio arranca en prueba
     * gratuita. `idPlan` sigue siendo NOT NULL en BD (evita tocar todo lo
     * que ya depende de Colegio::plan() para límites/módulos); esto solo
     * evita que alguien tenga que escogerlo a mano en ese caso.
     */
    private function planParaPrueba(): Plan
    {
        return Plan::orderByDesc('orden')->firstOrFail();
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

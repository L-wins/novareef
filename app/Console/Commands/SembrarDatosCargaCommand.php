<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Commands\Support\GeneradorDatosColombianos;
use App\Models\Colegio;
use App\Models\DivisionTorneo;
use App\Models\FormatoDesignacion;
use App\Models\Plan;
use App\Models\RolPartido;
use App\Models\SedeTorneo;
use App\Models\Torneo;
use App\Models\User;
use App\Services\ColegioService;
use App\Services\DesignacionService;
use App\Services\LimiteService;
use App\StateMachines\PartidoStateMachine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;

/**
 * Siembra datos de carga realistas para pruebas de estrés/rendimiento:
 * 5 colegios nuevos (uno por plan) + refuerzo de ASOCAFA a 150 árbitros.
 * Usa los Services reales (ColegioService, DesignacionService) para no saltarse
 * reglas de negocio — nunca inserta directo por DB::table() en tablas con lógica
 * de dominio (slots, historial, límites de plan).
 *
 * Neutraliza correo (Resend real)/colas/broadcast (Reverb puede no estar
 * corriendo) con los fakes de testing — mismo patrón que usan los Feature
 * tests, aplicado aquí a un comando real en vez de a PHPUnit.
 */
class SembrarDatosCargaCommand extends Command
{
    protected $signature = 'novareef:sembrar-carga
        {--solo-asocafa : Solo refuerza ASOCAFA, sin crear los 5 colegios nuevos}
        {--sin-partidos : No genera torneos/partidos/designaciones, solo colegios/árbitros/cuentas}';

    protected $description = 'Siembra colegios, árbitros, cuentas y partidos con volumen realista para pruebas de carga';

    private const PASSWORD_COMUN = 'password';

    /** Roles admin a crear en cada colegio, en el orden pedido — sin veedor. */
    private const ROLES_ADMIN_SIN_VEEDOR = ['ejecutivo', 'tesorero', 'designador', 'sanciones', 'tecnico'];

    private GeneradorDatosColombianos $datos;

    /** @var array<int, array{colegio:string,codigo:string,email:string,password:string,rol:string}> */
    private array $credencialesGeneradas = [];

    public function handle(LimiteService $limites, ColegioService $colegioService, DesignacionService $designacionService): int
    {
        // Reproducible: misma corrida = mismos nombres/documentos si se repite.
        mt_srand(20260722);
        $this->datos = new GeneradorDatosColombianos();

        // No disparar correos reales (MAIL_MAILER=resend) ni encolar jobs en
        // la tabla `jobs` real (QUEUE_CONNECTION=database) — mismo aislamiento
        // que usan los tests. Event::fake() NO se usa aquí a propósito: apaga
        // el dispatcher de eventos completo, incluidos los model events de
        // Eloquent (creating/saving) — Arbitro::booted() depende de
        // static::creating() para autogenerar codigoCarnet/estadoArbitro, así
        // que Event::fake() rompía todo insert de Arbitro con un error de SQL
        // sin relación aparente ("codigoCarnet doesn't have a default value").
        // broadcast() sin Reverb corriendo no lanza excepción (confirmado),
        // así que no hace falta neutralizarlo.
        Mail::fake();
        Queue::fake();

        $this->info('=== Sembrando datos de carga NovaReef ===');

        if (! $this->option('solo-asocafa')) {
            $this->sembrarColegiosNuevos($colegioService, $limites, $designacionService);
        }

        $this->reforzarAsocafa($limites);

        $this->guardarCredenciales();

        $this->info('=== Listo ===');

        return self::SUCCESS;
    }

    // ── 5 colegios nuevos ──────────────────

    /**
     * @return void
     */
    private function sembrarColegiosNuevos(ColegioService $colegioService, LimiteService $limites, DesignacionService $designacionService): void
    {
        $planes = Plan::orderBy('orden')->get()->keyBy('nombre');

        // orden -> [plan, arbitros_objetivo, proporcion_partidos]
        // Proporciones de partidos deliberadamente distintas por colegio para
        // generar variedad de casos borde (ver plan de pruebas / informe final).
        $definicion = [
            [
                'nombre' => 'Liga de Árbitros del Valle',
                'ciudad' => 'Cali', 'departamento' => 'Valle del Cauca',
                'plan' => 'Rookie', 'arbitrosObjetivo' => 60,
                'mezclaPartidos' => ['finalizado' => 0.55, 'cancelado' => 0.20, 'programado' => 0.20, 'aplazado' => 0.05],
                'totalPartidos' => 40,
            ],
            [
                'nombre' => 'Colegio de Árbitros de Antioquia',
                'ciudad' => 'Medellín', 'departamento' => 'Antioquia',
                'plan' => 'Goliath', 'arbitrosObjetivo' => 110,
                'mezclaPartidos' => ['finalizado' => 0.70, 'cancelado' => 0.10, 'programado' => 0.15, 'aplazado' => 0.05],
                'totalPartidos' => 90,
            ],
            [
                'nombre' => 'Corporación Arbitral de Santander',
                'ciudad' => 'Bucaramanga', 'departamento' => 'Santander',
                'plan' => 'Zenith', 'arbitrosObjetivo' => 160,
                'mezclaPartidos' => ['finalizado' => 0.60, 'cancelado' => 0.25, 'programado' => 0.10, 'aplazado' => 0.05],
                'totalPartidos' => 150,
            ],
            [
                'nombre' => 'Federación Arbitral del Eje Cafetero',
                'ciudad' => 'Pereira', 'departamento' => 'Risaralda',
                'plan' => 'GodMode', 'arbitrosObjetivo' => 200,
                'mezclaPartidos' => ['finalizado' => 0.50, 'cancelado' => 0.15, 'programado' => 0.30, 'aplazado' => 0.05],
                'totalPartidos' => 200,
            ],
            [
                'nombre' => 'Asociación de Árbitros de la Costa',
                'ciudad' => 'Barranquilla', 'departamento' => 'Atlántico',
                'plan' => 'Zenith', 'arbitrosObjetivo' => 150,
                'mezclaPartidos' => ['finalizado' => 0.80, 'cancelado' => 0.05, 'programado' => 0.10, 'aplazado' => 0.05],
                'totalPartidos' => 300,
            ],
        ];

        foreach ($definicion as $i => $def) {
            $this->info("\n--- Colegio " . ($i + 1) . "/5: {$def['nombre']} (plan {$def['plan']}) ---");

            $codigo = 'NR' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);

            $colegioExistente = Colegio::where('codigoColegio', $codigo)->first();

            if ($colegioExistente) {
                $this->line("Ya existe (idColegio={$colegioExistente->idColegio}), reutilizando.");
                $colegio = $colegioExistente;

                // Reejecuciones idempotentes: si el colegio ya existía de una
                // corrida previa (ej. cortada a mitad de camino), su
                // ejecutivo pudo quedar con la password aleatoria real de
                // ColegioService::registrar() en vez de la fija — sin esto,
                // una corrida parcial dejaba credenciales inconsistentes que
                // el usuario no podía usar (bug real detectado en la primera
                // corrida de este comando: el colegio 1 quedó así).
                $ejecutivoExistente = $colegioService->adminPrincipal($colegio);
                if ($ejecutivoExistente) {
                    $ejecutivoExistente->update(['passwordUsuario' => self::PASSWORD_COMUN]);
                    $this->registrarCredencial($def['nombre'], $codigo, $ejecutivoExistente->emailUsuario, 'ejecutivo');
                }
            } else {
                $plan = $planes->get($def['plan']);
                $emailAdmin = "ejecutivo.{$this->datos->slug($def['nombre'])}@test.com";

                $colegio = $colegioService->registrar(
                    nombreColegio: $def['nombre'],
                    codigoColegio: $codigo,
                    emailColegio: "contacto@{$this->datos->slug($def['nombre'])}.test.com",
                    telefonoColegio: $this->datos->telefono(),
                    direccionColegio: 'Calle ' . mt_rand(1, 150) . ' # ' . mt_rand(1, 99) . '-' . mt_rand(1, 99),
                    ciudadColegio: $def['ciudad'],
                    departamentoColegio: $def['departamento'],
                    paisColegio: 'Colombia',
                    logoColegio: null,
                    idPlan: $plan->idPlan,
                    nombreAdmin: $this->datos->nombreCompleto()['nombre'],
                    emailAdmin: $emailAdmin,
                    iniciarComoTrial: false,
                );

                // Password fija en vez de la aleatoria que genera el flujo real
                // (registrarConCredenciales usa PasswordGenerator) — el usuario
                // pidió credenciales fáciles de usar para entrar a probar.
                $ejecutivo = $colegioService->adminPrincipal($colegio);
                $ejecutivo->update(['passwordUsuario' => self::PASSWORD_COMUN]);
                $this->registrarCredencial($def['nombre'], $codigo, $ejecutivo->emailUsuario, 'ejecutivo');

                $this->info("Creado idColegio={$colegio->idColegio}, tenant={$colegio->tenantId}");
            }

            $cuentas = $this->crearCuentasAdmin($colegio, self::ROLES_ADMIN_SIN_VEEDOR, $limites, incluirVeedor: true);
            foreach ($cuentas as $c) {
                $this->registrarCredencial($def['nombre'], $codigo, $c->emailUsuario, $c->rolUsuario);
            }

            $arbitros = $this->crearArbitros($colegio, $def['arbitrosObjetivo'], $limites);
            $this->info('Árbitros en colegio: ' . count($arbitros) . " (objetivo {$def['arbitrosObjetivo']}, límite de plan aplicado si correspondía)");

            if (! $this->option('sin-partidos') && count($arbitros) >= 2) {
                $this->sembrarPartidos($colegio, $arbitros, $def['totalPartidos'], $def['mezclaPartidos'], $designacionService, incluirVeedor: true);
            }
        }
    }

    // ── ASOCAFA ─────────────────────────────

    private function reforzarAsocafa(LimiteService $limites): void
    {
        $this->info("\n--- Reforzando ASOCAFA ---");

        $asocafa = Colegio::where('codigoColegio', 'ASOCAFA')->orWhere('nombreColegio', 'ASOCAFA')->first();

        if (! $asocafa) {
            $this->error('No se encontró el colegio ASOCAFA — nada que reforzar. Créalo primero desde el panel admin.');
            return;
        }

        // GodMode: sin tope de árbitros/cuentas, para que 150 árbitros + 5
        // cuentas nunca se vean bloqueadas por límite de plan. La suscripción
        // existente estaba en trial (7 días) — se estabiliza a activa con
        // vencimiento largo para que los datos sembrados no expiren en la
        // mitad de las pruebas.
        $godMode = Plan::where('nombre', 'GodMode')->firstOrFail();

        $suscripcion = $asocafa->suscripciones()->latest('idSuscripcion')->first();
        if ($suscripcion) {
            $suscripcion->update([
                'idPlan' => $godMode->idPlan,
                'estado' => 'activa',
                'fechaVencimiento' => today()->addYear(),
            ]);
            $this->line('Suscripción de ASOCAFA pasada a activa/GodMode, vence en 1 año.');
        }

        // El ejecutivo de ASOCAFA ya existía antes de este comando (creado
        // manualmente desde el panel admin, con su propia password) — se
        // fija a la password común y se registra en el archivo de
        // credenciales igual que el resto de cuentas sembradas, para que el
        // usuario tenga un único punto de acceso a todos los colegios.
        $ejecutivoAsocafa = User::where('idColegio', $asocafa->idColegio)->where('rolUsuario', 'ejecutivo')->first();
        if ($ejecutivoAsocafa) {
            $ejecutivoAsocafa->update(['passwordUsuario' => self::PASSWORD_COMUN]);
            $this->registrarCredencial('ASOCAFA', 'ASOCAFA', $ejecutivoAsocafa->emailUsuario, 'ejecutivo');
        }

        $actuales = $limites->arbitrosActivos($asocafa->idColegio);
        $faltantes = max(0, 150 - $actuales);

        $this->line("Árbitros actuales: {$actuales}. Creando {$faltantes} para llegar a 150.");

        $nuevos = $this->crearArbitros($asocafa, $faltantes, $limites);
        $this->info('Árbitros creados en ASOCAFA: ' . count($nuevos));

        // Sin veedor en ASOCAFA (pedido explícito del usuario).
        $cuentas = $this->crearCuentasAdmin($asocafa, self::ROLES_ADMIN_SIN_VEEDOR, $limites, incluirVeedor: false);
        foreach ($cuentas as $c) {
            $this->registrarCredencial('ASOCAFA', 'ASOCAFA', $c->emailUsuario, $c->rolUsuario);
        }
        $this->info('Cuentas admin creadas en ASOCAFA (sin veedor): ' . count($cuentas));
    }

    // ── Árbitros ────────────────────────────

    /**
     * @return array<int, \App\Models\Arbitro>
     */
    private function crearArbitros(Colegio $colegio, int $cantidad, LimiteService $limites): array
    {
        if ($cantidad <= 0) {
            return [];
        }

        $categoriaIds = DB::table('categorias_arbitro')
            ->where('idColegio', $colegio->idColegio)
            ->pluck('idCategoria')
            ->all();

        if (empty($categoriaIds)) {
            $this->warn("Colegio {$colegio->idColegio} sin categorías — omitiendo árbitros.");
            return [];
        }

        $estados = ['activo', 'activo', 'activo', 'activo', 'proceso_ingreso', 'inactivo'];
        $creados = [];

        for ($n = 0; $n < $cantidad; $n++) {
            if (! $limites->puedeCrearArbitro($colegio->idColegio)) {
                $this->warn('Límite de árbitros del plan alcanzado en idColegio=' . $colegio->idColegio . " tras {$n}/{$cantidad} — cortando aquí (comportamiento esperado de LimiteService).");
                break;
            }

            $persona = $this->datos->nombreCompleto();
            $slugColegio = $this->datos->slug($colegio->codigoColegio);
            $indiceGlobal = DB::table('usuarios')->where('idColegio', $colegio->idColegio)->count() + 1;

            $usuario = User::create([
                'idColegio' => $colegio->idColegio,
                'nombreUsuario' => $persona['nombre'],
                'emailUsuario' => "arbitro{$indiceGlobal}.{$slugColegio}@test.com",
                'passwordUsuario' => self::PASSWORD_COMUN,
                'telefonoUsuario' => $this->datos->telefono(),
                'rolUsuario' => 'arbitro',
                'estadoUsuario' => 'activo',
                'temaPreferencia' => 'dark',
            ]);
            $usuario->assignRole('arbitro');

            $barrio = $this->datos->barrio();
            $tieneVehiculo = mt_rand(0, 4) === 0;

            $arbitro = \App\Models\Arbitro::create([
                'idUsuario' => $usuario->idUsuario,
                'idColegio' => $colegio->idColegio,
                'idCategoria' => $categoriaIds[array_rand($categoriaIds)],
                'tipoDocumento' => 'cedula',
                'numeroDocumento' => $this->datos->numeroDocumento(),
                'lugarExpedicionCC' => $colegio->ciudadColegio,
                'pesoArbitro' => mt_rand(58, 92),
                'estaturaArbitro' => round(mt_rand(160, 195) / 100, 2),
                'rhArbitro' => $this->datos->tipoSangre(),
                'epsArbitro' => $this->datos->eps(),
                'profesionArbitro' => $this->datos->profesion(),
                'fechaIngresoColegio' => now()->subDays(mt_rand(30, 1500))->toDateString(),
                'direccionArbitro' => 'Calle ' . mt_rand(1, 150) . ' # ' . mt_rand(1, 99) . '-' . mt_rand(1, 99),
                'barrioArbitro' => $barrio,
                'tieneVehiculo' => $tieneVehiculo,
                'tipoVehiculo' => $tieneVehiculo ? (mt_rand(0, 1) ? 'moto' : 'carro') : null,
                'marcaVehiculo' => $tieneVehiculo ? 'N/D' : null,
                'placaVehiculo' => $tieneVehiculo ? strtoupper(substr(md5((string) mt_rand()), 0, 3)) . mt_rand(100, 999) : null,
                'estadoArbitro' => $estados[array_rand($estados)],
            ]);

            $creados[] = $arbitro;
        }

        return $creados;
    }

    // ── Cuentas admin ──────────────────────

    /**
     * @param  list<string>  $rolesBase
     * @return array<int, User>
     */
    private function crearCuentasAdmin(Colegio $colegio, array $rolesBase, LimiteService $limites, bool $incluirVeedor): array
    {
        $roles = $incluirVeedor ? [...$rolesBase, 'veedor'] : $rolesBase;
        $creadas = [];

        foreach ($roles as $rol) {
            $yaExiste = User::where('idColegio', $colegio->idColegio)
                ->where('rolUsuario', $rol)
                ->exists();

            if ($yaExiste) {
                continue;
            }

            if (! $limites->puedeCrearCuentaAdmin($colegio->idColegio)) {
                $this->warn("Límite de cuentas admin del plan alcanzado en idColegio={$colegio->idColegio} — no se crea cuenta '{$rol}' (comportamiento esperado de LimiteService).");
                continue;
            }

            if (! Role::where('name', $rol)->where('guard_name', 'web')->exists()) {
                $this->warn("Rol Spatie '{$rol}' no existe — omitiendo cuenta.");
                continue;
            }

            $persona = $this->datos->nombreCompleto();
            $slugColegio = $this->datos->slug($colegio->codigoColegio);

            $usuario = User::create([
                'idColegio' => $colegio->idColegio,
                'nombreUsuario' => $persona['nombre'],
                'emailUsuario' => "{$rol}.{$slugColegio}@test.com",
                'passwordUsuario' => self::PASSWORD_COMUN,
                'telefonoUsuario' => $this->datos->telefono(),
                'rolUsuario' => $rol,
                'estadoUsuario' => 'activo',
                'temaPreferencia' => 'dark',
            ]);
            $usuario->assignRole($rol);

            $creadas[] = $usuario;
        }

        return $creadas;
    }

    // ── Torneos / partidos / designaciones ──

    /**
     * @param  array<int, \App\Models\Arbitro>  $arbitros
     * @param  array<string, float>  $mezcla  clave=estado destino, valor=proporción (suma ~1.0)
     */
    private function sembrarPartidos(Colegio $colegio, array $arbitros, int $total, array $mezcla, DesignacionService $designacionService, bool $incluirVeedor): void
    {
        $this->crearRolesYFormatosSiFaltan();

        $designador = User::where('idColegio', $colegio->idColegio)->where('rolUsuario', 'designador')->first()
            ?? User::where('idColegio', $colegio->idColegio)->where('rolUsuario', 'ejecutivo')->first();

        if (! $designador) {
            $this->warn("Sin designador/ejecutivo en idColegio={$colegio->idColegio} — omitiendo partidos.");
            return;
        }

        $veedor = $incluirVeedor
            ? User::where('idColegio', $colegio->idColegio)->where('rolUsuario', 'veedor')->first()
            : null;

        $torneo = Torneo::firstOrCreate(
            ['idColegio' => $colegio->idColegio, 'nombreTorneo' => 'Liga ' . now()->year],
            [
                'tipoTorneo' => 'local',
                'modalidadPago' => 'nomina',
                'estadoTorneo' => 'activo',
                'organizadorNombre' => $colegio->nombreColegio,
                'temporada' => (int) now()->year,
                'fechaInicio' => now()->subMonths(6)->toDateString(),
                'fechaFin' => now()->addMonths(3)->toDateString(),
                'idUsuarioCreador' => $designador->idUsuario,
            ],
        );

        $division = DivisionTorneo::firstOrCreate(
            ['idTorneo' => $torneo->idTorneo, 'nombreDivision' => 'Primera División'],
        );

        $sede = SedeTorneo::firstOrCreate(
            ['idTorneo' => $torneo->idTorneo, 'nombreSede' => 'Complejo Deportivo Municipal'],
            ['ciudad' => $colegio->ciudadColegio ?? 'Bogotá'],
        );

        $formato = FormatoDesignacion::where('nombre', 'Dupla')->firstOrFail();
        $rolCentral = RolPartido::where('nombre', 'Central')->value('idRol');
        $rolAsistente = RolPartido::where('nombre', 'Asistente')->value('idRol');

        $arbitrosDesignables = array_values(array_filter($arbitros, fn ($a) => $a->puedeSerDesignado()));

        if (count($arbitrosDesignables) < 2) {
            $this->warn("Colegio {$colegio->idColegio} sin suficientes árbitros designables — omitiendo partidos.");
            return;
        }

        $secuenciaEstados = $this->expandirMezcla($mezcla, $total);
        $creados = 0;
        $bar = $this->output->createProgressBar($total);

        foreach ($secuenciaEstados as $estadoObjetivo) {
            [$local, $visitante] = $this->datos->dosEquiposDistintos();

            $fechaPartido = $estadoObjetivo === 'programado'
                ? now()->addDays(mt_rand(1, 60))->toDateString()
                : now()->subDays(mt_rand(1, 400))->toDateString();

            $partido = $designacionService->crearPartido($colegio->idColegio, [
                'idTorneo' => $torneo->idTorneo,
                'idDivision' => $division->idDivision,
                'idSede' => $sede->idSede,
                'idFormato' => $formato->idFormato,
                'equipoLocal' => $local,
                'equipoVisitante' => $visitante,
                'fechaPartido' => $fechaPartido,
                'horaPartido' => sprintf('%02d:%02d', mt_rand(8, 19), mt_rand(0, 1) * 30),
                'observaciones' => null,
            ], $designador->idUsuario);

            shuffle($arbitrosDesignables);
            $central = $arbitrosDesignables[0];
            $asistente = $arbitrosDesignables[1];

            $designacionService->asignarArbitro($partido, $central->idArbitro, $rolCentral, $colegio->idColegio, $designador->idUsuario);
            $designacionService->asignarArbitro($partido, $asistente->idArbitro, $rolAsistente, $colegio->idColegio, $designador->idUsuario);

            if ($veedor && mt_rand(0, 1) === 1) {
                $designacionService->asignarVeedor($partido, $veedor->idUsuario, $colegio->idColegio, $designador->idUsuario);
            }

            $designacionService->publicarPartido($partido->fresh('formato'), $designador);
            $partido->refresh();

            $this->avanzarPartidoAEstado($partido, $estadoObjetivo, $designador);

            $creados++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Partidos creados en idColegio={$colegio->idColegio}: {$creados}");
    }

    /**
     * Lleva un partido recién publicado ('programado') hasta el estado
     * objetivo pedido por la mezcla, pasando por transiciones válidas de
     * PartidoStateMachine::TRANSICIONES — nunca salta estados.
     */
    private function avanzarPartidoAEstado(\App\Models\Partido $partido, string $objetivo, User $usuario): void
    {
        match ($objetivo) {
            'programado' => null, // ya quedó ahí tras publicar
            'cancelado' => PartidoStateMachine::transicionarCon($partido, 'cancelado', $usuario, 'Cancelado por lluvia / cancha no disponible (dato de prueba)'),
            'aplazado' => PartidoStateMachine::transicionarCon($partido, 'aplazado', $usuario, 'Aplazado por disponibilidad de sede (dato de prueba)'),
            'finalizado' => $this->finalizarPartido($partido, $usuario),
            default => null,
        };
    }

    private function finalizarPartido(\App\Models\Partido $partido, User $usuario): void
    {
        // confirmarDesignacion() exige la designación pendiente real, no
        // vamos a re-simular la confirmación de cada árbitro (ya se probó ese
        // flujo en DesignacionFlujoTest) — para el volumen de datos de carga
        // basta con llevar el partido por sus transiciones de estado válidas
        // usando la state machine directamente: programado -> confirmado -> finalizado.
        PartidoStateMachine::transicionarCon($partido, 'confirmado', $usuario, 'Confirmado (dato de prueba)');
        PartidoStateMachine::transicionarCon($partido->fresh(), 'finalizado', $usuario, 'Finalizado (dato de prueba)');
    }

    /** @return list<string> */
    private function expandirMezcla(array $mezcla, int $total): array
    {
        $secuencia = [];
        foreach ($mezcla as $estado => $proporcion) {
            $cantidad = (int) round($total * $proporcion);
            $secuencia = [...$secuencia, ...array_fill(0, $cantidad, $estado)];
        }

        // Completar/recortar por redondeo para que la cuenta final sea exacta.
        while (count($secuencia) < $total) {
            $secuencia[] = array_key_first($mezcla);
        }
        $secuencia = array_slice($secuencia, 0, $total);

        shuffle($secuencia);

        return $secuencia;
    }

    private function crearRolesYFormatosSiFaltan(): void
    {
        // Ya sembrados globalmente por RolesPartidoSeeder/FormatosDesignacionSeeder
        // en instalaciones normales — este método es solo un resguardo idempotente
        // si el comando corre contra una BD que aún no los tiene.
        if (RolPartido::count() === 0) {
            $this->call('db:seed', ['--class' => 'RolesPartidoSeeder']);
        }
        if (FormatoDesignacion::count() === 0) {
            $this->call('db:seed', ['--class' => 'FormatosDesignacionSeeder']);
        }
    }

    // ── Credenciales ────────────────────────

    private function registrarCredencial(string $colegio, string $codigo, ?string $email, string $rol): void
    {
        if ($email === null) {
            return;
        }

        $this->credencialesGeneradas[] = [
            'colegio' => $colegio,
            'codigo' => $codigo,
            'email' => $email,
            'password' => self::PASSWORD_COMUN,
            'rol' => $rol,
        ];
    }

    private function guardarCredenciales(): void
    {
        if (empty($this->credencialesGeneradas)) {
            return;
        }

        $lineas = ["# Credenciales de datos de carga — todas con password: " . self::PASSWORD_COMUN, ''];
        $porColegio = [];
        foreach ($this->credencialesGeneradas as $c) {
            $porColegio[$c['colegio']][] = $c;
        }

        foreach ($porColegio as $colegio => $cuentas) {
            $lineas[] = "## {$colegio} ({$cuentas[0]['codigo']})";
            foreach ($cuentas as $c) {
                $lineas[] = "- {$c['rol']}: {$c['email']}";
            }
            $lineas[] = '';
        }

        $path = storage_path('app/credenciales-datos-carga.md');
        file_put_contents($path, implode("\n", $lineas));
        $this->info("Credenciales guardadas en: {$path}");
    }
}

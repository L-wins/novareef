<?php

declare(strict_types=1);

namespace App\Console\Commands\Support;

/**
 * Pools de datos realistas para Colombia, usados solo por el comando de
 * seeding de carga (novareef:sembrar-carga). Determinístico via mt_srand
 * externo — esta clase no fija su propia semilla, la fija el comando que
 * la invoca para que toda la corrida sea reproducible con --fresh.
 */
final class GeneradorDatosColombianos
{
    private const NOMBRES_M = [
        'Juan', 'Carlos', 'Andrés', 'Luis', 'Jorge', 'Miguel', 'Santiago', 'Sebastián',
        'Diego', 'Camilo', 'Julián', 'Felipe', 'Alejandro', 'Ricardo', 'Fernando',
        'Óscar', 'Iván', 'Mauricio', 'Cristian', 'Daniel', 'David', 'Esteban',
        'Gustavo', 'Hernán', 'Jairo', 'Wilson', 'Yesid', 'Nelson', 'Rodrigo', 'Álvaro',
    ];

    private const NOMBRES_F = [
        'María', 'Laura', 'Andrea', 'Paula', 'Diana', 'Carolina', 'Natalia', 'Sandra',
        'Claudia', 'Alejandra', 'Adriana', 'Catalina', 'Valentina', 'Camila', 'Sofía',
        'Juliana', 'Angélica', 'Patricia', 'Liliana', 'Marcela', 'Yolanda', 'Ximena',
        'Gloria', 'Beatriz', 'Esperanza', 'Consuelo', 'Rocío', 'Viviana',
    ];

    private const APELLIDOS = [
        'Gómez', 'Rodríguez', 'González', 'Martínez', 'López', 'Sánchez', 'Pérez',
        'Ramírez', 'Torres', 'Díaz', 'Vargas', 'Castro', 'Ruiz', 'Álvarez', 'Romero',
        'Suárez', 'Rojas', 'Moreno', 'Muñoz', 'Ortiz', 'Herrera', 'Jiménez', 'Medina',
        'Castillo', 'Cortés', 'Guzmán', 'Peña', 'Vega', 'Reyes', 'Cardona', 'Osorio',
        'Zapata', 'Restrepo', 'Aguirre', 'Franco', 'Salazar', 'Mejía', 'Gil', 'Pineda',
        'Cárdenas', 'Bermúdez', 'Quintero', 'Valencia', 'Arias', 'Bedoya', 'Duque',
    ];

    /** [ciudad, departamento] — priorizando plazas reales de arbitraje colombiano. */
    private const CIUDADES = [
        ['Bogotá', 'Bogotá'],
        ['Medellín', 'Antioquia'],
        ['Cali', 'Valle del Cauca'],
        ['Barranquilla', 'Atlántico'],
        ['Bucaramanga', 'Santander'],
        ['Pereira', 'Risaralda'],
        ['Manizales', 'Caldas'],
        ['Ibagué', 'Tolima'],
        ['Cúcuta', 'Norte de Santander'],
        ['Villavicencio', 'Meta'],
        ['Neiva', 'Huila'],
        ['Armenia', 'Quindío'],
        ['Popayán', 'Cauca'],
        ['Chía', 'Cundinamarca'],
        ['Tenjo', 'Cundinamarca'],
        ['Soacha', 'Cundinamarca'],
        ['Zipaquirá', 'Cundinamarca'],
        ['Facatativá', 'Cundinamarca'],
    ];

    private const BARRIOS = [
        'El Prado', 'Los Alcázares', 'Santa Bárbara', 'La Castellana', 'Kennedy',
        'Suba', 'Chapinero', 'Ciudad Jardín', 'El Poblado', 'Laureles', 'Belén',
        'San Fernando', 'Cristo Rey', 'La América', 'El Recuerdo', 'Villa del Prado',
        'Bosque Popular', 'Modelia', 'Restrepo', 'Fontibón', 'Engativá', 'Usaquén',
    ];

    private const EPS = [
        'Sura', 'Sanitas', 'Nueva EPS', 'Compensar', 'Famisanar', 'Salud Total',
        'Coosalud', 'Mutual Ser', 'Aliansalud', 'Comfenalco',
    ];

    private const PROFESIONES = [
        'Estudiante', 'Docente', 'Comerciante', 'Ingeniero', 'Administrador',
        'Contador', 'Abogado', 'Técnico deportivo', 'Entrenador', 'Fisioterapeuta',
        'Conductor', 'Independiente', 'Empleado', 'Policía', 'Militar retirado',
    ];

    private const TIPOS_SANGRE = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];

    private const EQUIPOS = [
        'Halcones FC', 'Titanes', 'Deportivo Central', 'Águilas Doradas', 'Los Andes',
        'Cóndores', 'Real Sabana', 'Unión Deportiva', 'Atlético Norte', 'Estrella Roja',
        'Independiente Sur', 'Los Cerros', 'Junior Cundinamarca', 'Bravos FC',
        'Deportivo Chía', 'Real Tenjo', 'Los Guerreros', 'Fénix FC', 'Rayo Andino',
        'Deportivo Sabana', 'Once Estrellas', 'Los Tigres', 'Alianza FC', 'Vencedores',
    ];

    private int $secuenciaDocumento = 1_000_000;

    public function nombreCompleto(): array
    {
        $esMujer = mt_rand(0, 1) === 1;
        $nombre  = $esMujer ? self::NOMBRES_F[array_rand(self::NOMBRES_F)] : self::NOMBRES_M[array_rand(self::NOMBRES_M)];
        $ap1     = self::APELLIDOS[array_rand(self::APELLIDOS)];
        $ap2     = self::APELLIDOS[array_rand(self::APELLIDOS)];

        return ['nombre' => "{$nombre} {$ap1} {$ap2}", 'esMujer' => $esMujer];
    }

    public function ciudad(): array
    {
        [$ciudad, $departamento] = self::CIUDADES[array_rand(self::CIUDADES)];

        return ['ciudad' => $ciudad, 'departamento' => $departamento];
    }

    public function barrio(): string
    {
        return self::BARRIOS[array_rand(self::BARRIOS)];
    }

    public function eps(): string
    {
        return self::EPS[array_rand(self::EPS)];
    }

    public function profesion(): string
    {
        return self::PROFESIONES[array_rand(self::PROFESIONES)];
    }

    public function tipoSangre(): string
    {
        return self::TIPOS_SANGRE[array_rand(self::TIPOS_SANGRE)];
    }

    public function equipo(): string
    {
        return self::EQUIPOS[array_rand(self::EQUIPOS)];
    }

    public function dosEquiposDistintos(): array
    {
        $local = $this->equipo();
        do {
            $visitante = $this->equipo();
        } while ($visitante === $local);

        return [$local, $visitante];
    }

    /** Cédula colombiana de 8-10 dígitos, secuencial dentro de la corrida (sin colisiones entre árbitros). */
    public function numeroDocumento(): string
    {
        return (string) $this->secuenciaDocumento++;
    }

    public function telefono(): string
    {
        return '3' . str_pad((string) mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT);
    }

    public function slug(string $texto): string
    {
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ñ' => 'n',
        ]);

        return strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $texto), '-'));
    }
}

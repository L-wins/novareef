<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfiguracionColegio extends Model
{
    protected $table      = 'configuracion_colegio';
    protected $primaryKey = 'idConfiguracion';
    protected $keyType    = 'int';
    public    $incrementing = true;

    // ── Claves de configuración ───────────────────────────────────────────────
    public const DIA_DISPONIBILIDAD = 'dia_disponibilidad';

    private const DESCRIPCIONES = [
        self::DIA_DISPONIBILIDAD => 'Día de la semana en que los árbitros deben reportar disponibilidad (1=Lunes ... 7=Domingo)',
    ];

    protected $fillable = [
        'idColegio',
        'clave',
        'valor',
        'descripcion',
    ];

    // ── Métodos estáticos ─────────────────────────────────────────────────────

    /**
     * Obtiene el valor de una clave para un colegio, o retorna el default.
     */
    public static function get(int $idColegio, string $clave, mixed $default = null): mixed
    {
        $config = static::where('idColegio', $idColegio)
                        ->where('clave', $clave)
                        ->first();

        return $config !== null ? $config->valor : $default;
    }

    /**
     * Crea o actualiza una clave de configuración para un colegio.
     * La descripción se toma de DESCRIPCIONES si no se pasa explícitamente.
     */
    public static function set(
        int $idColegio,
        string $clave,
        mixed $valor,
        ?string $descripcion = null,
    ): static {
        return static::updateOrCreate(
            ['idColegio' => $idColegio, 'clave' => $clave],
            ['valor' => (string) $valor, 'descripcion' => $descripcion ?? self::DESCRIPCIONES[$clave] ?? null],
        );
    }

    /**
     * Devuelve el mapa completo de días (1–7) para poblar selects de vista.
     * Centraliza la construcción que antes se hacía con un loop en el controlador.
     */
    public static function diasSemana(): array
    {
        return array_map(
            fn (int $i) => static::getNombreDia($i),
            range(1, 7),
        );
    }

    /**
     * Retorna el día configurado para reporte de disponibilidad (1=Lunes…7=Domingo).
     */
    public static function getDiaDisponibilidad(int $idColegio): int
    {
        $valor = (int) static::get($idColegio, static::DIA_DISPONIBILIDAD, '1');

        return ($valor >= 1 && $valor <= 7) ? $valor : 1;
    }

    /**
     * Retorna el nombre del día de la semana dado su número (1=Lunes…7=Domingo).
     */
    public static function getNombreDia(int $numero): string
    {
        return match ($numero) {
            1       => 'Lunes',
            2       => 'Martes',
            3       => 'Miércoles',
            4       => 'Jueves',
            5       => 'Viernes',
            6       => 'Sábado',
            7       => 'Domingo',
            default => 'Lunes',
        };
    }

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'idColegio', 'idColegio');
    }
}

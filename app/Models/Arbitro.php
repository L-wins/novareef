<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Arbitro extends Model
{
    use HasFactory, SoftDeletes;

    protected $table      = 'arbitros';
    protected $primaryKey = 'idArbitro';
    protected $keyType    = 'int';
    public    $incrementing = true;

    /**
     * Estados en los que un árbitro NO puede ser designado a partidos.
     */
    private const ESTADOS_NO_DESIGNABLES = ['proceso_ingreso', 'suspendido', 'retirado'];

    protected $fillable = [
        'idUsuario',
        'idColegio',
        'idCategoria',
        'numeroDocumento',
        'tipoDocumento',
        'lugarExpedicionCC',
        'pesoArbitro',
        'estaturaArbitro',
        'rhArbitro',
        'epsArbitro',
        'profesionArbitro',
        'fechaIngresoColegio',
        'direccionArbitro',
        'barrioArbitro',
        'tieneVehiculo',
        'tipoVehiculo',
        'marcaVehiculo',
        'placaVehiculo',
        'colorVehiculo',
        'codigoCarnet',
        'estadoArbitro',
    ];

    protected $casts = [
        'fechaIngresoColegio' => 'date',
        'tieneVehiculo'       => 'boolean',
        'pesoArbitro'         => 'decimal:2',
        'estaturaArbitro'     => 'decimal:2',
        'deleted_at'          => 'datetime',
    ];

    // ── Eventos del modelo ───────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Arbitro $arbitro): void {
            // Regla: estado inicial siempre 'proceso_ingreso'.
            if (empty($arbitro->estadoArbitro)) {
                $arbitro->estadoArbitro = 'proceso_ingreso';
            }

            // Regla: generación automática del código de carné.
            if (empty($arbitro->codigoCarnet)) {
                $arbitro->codigoCarnet = self::generarCodigoCarnet((int) $arbitro->idColegio);
            }
        });

        // Regla: sin vehículo, los datos del vehículo se anulan. Se aplica en
        // 'saving' (no en un mutador) para ser independiente del orden en que
        // se asignan los atributos al crear o actualizar.
        static::saving(function (Arbitro $arbitro): void {
            if (! $arbitro->tieneVehiculo) {
                $arbitro->tipoVehiculo  = null;
                $arbitro->marcaVehiculo = null;
                $arbitro->placaVehiculo = null;
                $arbitro->colorVehiculo = null;
            }
        });
    }

    // ── Reglas de negocio ────────────────────────────────────────────────────

    /**
     * Genera el código del carné con formato NR-{idColegio}-{año}-{secuencial}.
     * El secuencial se calcula por colegio y año; incluye registros con SoftDelete
     * para no reutilizar un código ya emitido (la columna es UNIQUE).
     */
    public static function generarCodigoCarnet(int $idColegio): string
    {
        $anio    = now()->year;
        $prefijo = "NR-{$idColegio}-{$anio}-";

        $secuencial = self::withTrashed()
            ->where('idColegio', $idColegio)
            ->where('codigoCarnet', 'like', $prefijo . '%')
            ->count() + 1;

        return $prefijo . str_pad((string) $secuencial, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Indica si el árbitro puede ser designado a partidos (regla aplicada en M04).
     */
    public function puedeSerDesignado(): bool
    {
        return ! in_array($this->estadoArbitro, self::ESTADOS_NO_DESIGNABLES, true);
    }

    // ── Relaciones ───────────────────────────────────────────────────────────

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUsuario', 'idUsuario');
    }

    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'idColegio', 'idColegio');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaArbitro::class, 'idCategoria', 'idCategoria');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoArbitro::class, 'idArbitro', 'idArbitro');
    }
}

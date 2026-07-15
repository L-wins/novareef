<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
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
        'fotoPerfil',
        'codigoCarnet',
        'estadoArbitro',
        'scoreDesempeno',
    ];

    protected $casts = [
        'fechaIngresoColegio' => 'date',
        'tieneVehiculo'       => 'boolean',
        'pesoArbitro'         => 'decimal:2',
        'estaturaArbitro'     => 'decimal:2',
        'scoreDesempeno'      => 'decimal:2',
        'deleted_at'          => 'datetime',
    ];

    protected $appends = ['porcentajePerfil', 'colorPerfil'];

    //  Eventos del modelo 

    protected static function booted(): void
    {
        static::creating(function (Arbitro $arbitro): void {
            if (empty($arbitro->estadoArbitro)) {
                $arbitro->estadoArbitro = 'proceso_ingreso';
            }

            if (empty($arbitro->codigoCarnet)) {
                $arbitro->codigoCarnet = self::generarCodigoCarnet((int) $arbitro->idColegio);
            }
        });

        static::saving(function (Arbitro $arbitro): void {
            if (! $arbitro->tieneVehiculo) {
                $arbitro->tipoVehiculo  = null;
                $arbitro->marcaVehiculo = null;
                $arbitro->placaVehiculo = null;
                $arbitro->colorVehiculo = null;
            }
        });
    }

    //  Reglas de negocio ─

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

    public function puedeSerDesignado(): bool
    {
        return ! in_array($this->estadoArbitro, self::ESTADOS_NO_DESIGNABLES, true);
    }

    //  Accesores: porcentaje y color del perfil 

    public function getPorcentajePerfilAttribute(): int
    {
        $usuario = $this->usuario;

        // 20% — datos básicos
        $basicos = $usuario
            && ! empty($usuario->nombreUsuario)
            && ! empty($usuario->emailUsuario)
            && ! empty($usuario->telefonoUsuario)
            && ! empty($this->numeroDocumento)
            && ! empty($this->idCategoria)
            && ! empty($this->fechaIngresoColegio);
        $puntos = $basicos ? 20 : 0;

        // 15% — foto
        if (! empty($this->fotoPerfil)) {
            $puntos += 15;
        }

        // 20% — datos físicos
        $fisicos = ! empty($this->pesoArbitro)
            && ! empty($this->estaturaArbitro)
            && ! empty($this->rhArbitro)
            && ! empty($this->epsArbitro)
            && ! empty($this->profesionArbitro);
        if ($fisicos) {
            $puntos += 20;
        }

        // 15% — dirección y barrio
        if (! empty($this->direccionArbitro) && ! empty($this->barrioArbitro)) {
            $puntos += 15;
        }

        // 10% — vehículo configurado (decisión tomada)
        if ($this->tieneVehiculo !== null) {
            if ($this->tieneVehiculo) {
                if (! empty($this->tipoVehiculo) && ! empty($this->marcaVehiculo) && ! empty($this->placaVehiculo)) {
                    $puntos += 10;
                }
            } else {
                $puntos += 10;
            }
        }

        // 20% — documentos obligatorios subidos.
        // Usa la relación cargada (documentos) si ya está en memoria para evitar N+1.
        $docs = $this->relationLoaded('documentos')
            ? $this->documentos->where('obligatorio', true)
            : $this->documentos()->where('obligatorio', true)->get();

        if ($docs->isNotEmpty()) {
            $puntos += 20;
        }

        return min(100, max(0, $puntos));
    }

    public function getColorPerfilAttribute(): string
    {
        $p = $this->porcentajePerfil;

        return match (true) {
            $p >= 100 => 'green',
            $p >= 71  => 'blue',
            $p >= 41  => 'yellow',
            default   => 'red',
        };
    }

    //  Relaciones ─

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

    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoArbitro::class, 'estadoArbitro', 'nombre');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoArbitro::class, 'idArbitro', 'idArbitro');
    }

    public function historialEstados(): HasMany
    {
        return $this->hasMany(HistorialEstadoArbitro::class, 'idArbitro', 'idArbitro');
    }

    public function disponibilidades(): HasMany
    {
        return $this->hasMany(DisponibilidadArbitro::class, 'idArbitro', 'idArbitro');
    }

    public function indisponibilidadesExtraordinarias(): HasMany
    {
        return $this->hasMany(IndisponibilidadExtraordinaria::class, 'idArbitro', 'idArbitro');
    }

    public function designaciones(): HasMany
    {
        return $this->hasMany(Designacion::class, 'idArbitro', 'idArbitro');
    }

    public function calificaciones(): HasManyThrough
    {
        return $this->hasManyThrough(
            CalificacionArbitro::class,
            Designacion::class,
            'idArbitro',
            'idDesignacion',
            'idArbitro',
            'idDesignacion'
        );
    }

    public function getScorePromedioAttribute(): ?float
    {
        if (! $this->relationLoaded('calificaciones')) {
            $promedio = $this->calificaciones()->avg('nota');
        } else {
            $promedio = $this->calificaciones->avg('nota');
        }

        return $promedio !== null ? round((float) $promedio, 2) : null;
    }

    public function asistenciasAcademicas(): HasMany
    {
        return $this->hasMany(AsistenciaAcademica::class, 'idArbitro', 'idArbitro');
    }

    public function justificacionesAcademicas(): HasMany
    {
        return $this->hasMany(JustificacionAcademica::class, 'idArbitro', 'idArbitro');
    }

    /**
     * % de sesiones académicas marcadas como 'presente' sobre el total de
     * sesiones a las que el árbitro debía asistir. Null si aún no tiene
     * ninguna sesión asignada (evita dividir por cero / mostrar 0% engañoso).
     */
    public function getPorcentajeAsistenciaAttribute(): ?float
    {
        $asistencias = $this->relationLoaded('asistenciasAcademicas')
            ? $this->asistenciasAcademicas
            : $this->asistenciasAcademicas()->get();

        if ($asistencias->isEmpty()) {
            return null;
        }

        $presentes = $asistencias->where('estadoAsistencia', AsistenciaAcademica::ESTADO_PRESENTE)->count();

        return round(($presentes / $asistencias->count()) * 100, 1);
    }
}

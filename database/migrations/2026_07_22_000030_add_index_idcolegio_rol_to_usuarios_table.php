<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Diagnóstico real (auditoría de carga, colegio idColegio=10 con 206
 * usuarios): EXPLAIN sobre WHERE idColegio = ? AND rolUsuario = ? AND
 * estadoUsuario = ? (LimiteService::cuentasAdminActivas, cambiarEstadoPartido
 * en DesignacionAccionesController vía hasRole, y cualquier filtro por rol
 * dentro de un colegio) solo usaba usuarios_idcolegio_foreign — escaneaba
 * las 206 filas del colegio. Con volumen real (colegios GodMode/Zenith sin
 * tope de árbitros pueden superar varios cientos de usuarios), esto se
 * agrava. Índice compuesto para resolver colegio+rol en un solo acceso.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->index(['idColegio', 'rolUsuario'], 'idx_usuarios_colegio_rol');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropIndex('idx_usuarios_colegio_rol');
        });
    }
};

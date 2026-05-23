<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->engine  = 'InnoDB';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('idUsuario');

            // FK nullable: un usuario puede existir sin colegio (ej. superadmin)
            $table->unsignedBigInteger('idColegio')->nullable();

            $table->string('nombreUsuario', 150);
            $table->string('emailUsuario')->unique();
            $table->string('passwordUsuario');
            $table->string('telefonoUsuario', 20)->nullable();

            $table->enum('rolUsuario', [
                'arbitro',
                'ejecutivo',
                'tesorero',
                'designador',
                'sanciones',
                'tecnico',
                'superadmin',
            ]);

            $table->enum('estadoUsuario', ['activo', 'inactivo', 'suspendido'])
                  ->default('activo');

            $table->enum('temaPreferencia', ['oscuro', 'claro'])
                  ->default('oscuro');

            $table->string('tokenRecuperacion')->nullable();
            $table->timestamp('tokenExpiracion')->nullable();

            $table->boolean('dobleFactorActivo')->default(false);
            $table->string('dobleFactorCodigo', 10)->nullable();

            $table->timestamp('ultimoAcceso')->nullable();

            // Necesario para la funcionalidad "Recordarme" de Laravel
            $table->rememberToken();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('idColegio')
                  ->references('idColegio')
                  ->on('colegios')
                  ->onUpdate('cascade')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropForeign(['idColegio']);
        });

        Schema::dropIfExists('usuarios');
    }
};

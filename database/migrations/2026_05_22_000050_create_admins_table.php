<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table): void {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idAdmin');
            $table->string('nombre', 150);
            $table->string('email', 255)->unique();
            $table->string('password', 255);
            $table->string('google2fa_secret', 255)->nullable();
            $table->boolean('two_factor_enabled')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamp('ultimo_acceso')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};

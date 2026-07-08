<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_action_logs', function (Blueprint $table): void {
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idLog');

            $table->unsignedBigInteger('idAdmin')->nullable();
            $table->foreign('idAdmin')->references('idAdmin')->on('admins')->nullOnDelete();

            $table->string('accion', 100);
            $table->string('entidad', 100);
            $table->unsignedBigInteger('entidadId')->nullable();
            $table->text('detalle')->nullable();

            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_action_logs');
    }
};

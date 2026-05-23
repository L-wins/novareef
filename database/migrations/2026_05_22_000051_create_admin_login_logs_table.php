<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_login_logs', function (Blueprint $table): void {
            $table->engine = 'InnoDB';

            $table->bigIncrements('idLog');
            $table->string('ip', 45);
            $table->string('email', 255);
            $table->boolean('exitoso')->default(false);
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_login_logs');
    }
};

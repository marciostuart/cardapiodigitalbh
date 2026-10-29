<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mercado_pago_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('seller_id')->unique();
            $table->text('access_token');
            $table->text('refresh_token');
            $table->string('public_key')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('connected_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mercado_pago_connections');
    }
};

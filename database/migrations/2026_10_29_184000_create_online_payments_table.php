<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->uuid('external_reference')->unique();
            $table->string('preference_id')->nullable()->index();
            $table->string('provider_payment_id')->nullable()->unique();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('BRL');
            $table->string('status')->default('creating')->index();
            $table->string('provider_status')->nullable();
            $table->text('checkout_url')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_payments');
    }
};

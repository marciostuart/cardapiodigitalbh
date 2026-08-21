<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumer_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('phone', 13)->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        Schema::create('consumer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consumer_account_id')->constrained()->cascadeOnDelete();
            $table->string('postal_code', 9);
            $table->string('street');
            $table->string('number', 30);
            $table->string('complement')->nullable();
            $table->string('neighborhood');
            $table->string('city');
            $table->string('state', 2);
            $table->string('reference')->nullable();
            $table->boolean('is_default')->default(true);
            $table->timestamps();
        });

        Schema::create('consumer_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('consumer_account_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropConstrainedForeignId('consumer_account_id'));
        Schema::dropIfExists('consumer_password_reset_tokens');
        Schema::dropIfExists('consumer_addresses');
        Schema::dropIfExists('consumer_accounts');
    }
};

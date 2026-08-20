<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->boolean('table_service_enabled')->default(true)->after('manual_open_until');
            $table->boolean('pickup_enabled')->default(true)->after('table_service_enabled');
            $table->boolean('delivery_enabled')->default(false)->after('pickup_enabled');
            $table->decimal('minimum_order', 12, 2)->default(0)->after('delivery_enabled');
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->boolean('marketing_consent')->default(false);
            $table->timestamp('last_order_at')->nullable();
            $table->timestamps();
            $table->unique(['venue_id', 'phone']);
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('postal_code', 9);
            $table->string('street');
            $table->string('number', 30);
            $table->string('complement')->nullable();
            $table->string('neighborhood');
            $table->string('city');
            $table->string('state', 2);
            $table->string('reference')->nullable();
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('type');
            $table->decimal('value', 12, 2);
            $table->decimal('minimum_order', 12, 2)->default(0);
            $table->decimal('maximum_discount', 12, 2)->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['venue_id', 'code']);
        });

        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('fee', 12, 2);
            $table->text('neighborhoods')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('tab_id')->constrained()->nullOnDelete();
            $table->foreignId('delivery_zone_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->json('delivery_address')->nullable()->after('whatsapp_consent');
            $table->string('coupon_code')->nullable()->after('delivery_address');
            $table->decimal('discount', 12, 2)->default(0)->after('coupon_code');
            $table->decimal('delivery_fee', 12, 2)->default(0)->after('discount');
            $table->string('payment_method')->nullable()->after('delivery_fee');
            $table->decimal('change_for', 12, 2)->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropConstrainedForeignId('delivery_zone_id'));
        Schema::table('orders', fn (Blueprint $table) => $table->dropConstrainedForeignId('customer_id'));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['delivery_address', 'coupon_code', 'discount', 'delivery_fee', 'payment_method', 'change_for']));
        Schema::dropIfExists('delivery_zones');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('customers');
        Schema::table('venues', fn (Blueprint $table) => $table->dropColumn(['table_service_enabled', 'pickup_enabled', 'delivery_enabled', 'minimum_order']));
    }
};

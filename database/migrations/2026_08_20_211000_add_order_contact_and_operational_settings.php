<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('public_token', 64)->nullable()->unique()->after('id');
            $table->string('customer_name')->nullable()->after('station');
            $table->string('customer_phone')->nullable()->after('customer_name');
            $table->boolean('whatsapp_consent')->default(false)->after('customer_phone');
        });

        Schema::table('venues', function (Blueprint $table) {
            $table->boolean('geofence_enabled')->default(false)->after('catalog_sort_mode');
            $table->decimal('latitude', 10, 7)->nullable()->after('geofence_enabled');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->unsignedInteger('geofence_radius_meters')->default(100)->after('longitude');
            $table->string('manual_open_state')->nullable()->after('geofence_radius_meters');
            $table->timestamp('manual_open_until')->nullable()->after('manual_open_state');
        });

        Schema::create('venue_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('opens_at');
            $table->time('closes_at');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['venue_id', 'day_of_week']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('sku')->nullable()->after('name');
            $table->boolean('track_stock')->default(false)->after('price');
            $table->decimal('stock_on_hand', 14, 3)->default(0)->after('track_stock');
            $table->decimal('stock_reserved', 14, 3)->default(0)->after('stock_on_hand');
            $table->decimal('low_stock_threshold', 14, 3)->nullable()->after('stock_reserved');
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('unit', 20)->default('un');
            $table->decimal('stock_on_hand', 14, 3)->default(0);
            $table->decimal('stock_reserved', 14, 3)->default(0);
            $table->decimal('low_stock_threshold', 14, 3)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['venue_id', 'name']);
        });

        Schema::create('product_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->timestamps();
            $table->unique(['product_id', 'inventory_item_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->decimal('quantity', 14, 3);
            $table->decimal('balance_after', 14, 3);
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->index(['venue_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('product_recipes');
        Schema::dropIfExists('inventory_items');
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['sku', 'track_stock', 'stock_on_hand', 'stock_reserved', 'low_stock_threshold']));
        Schema::dropIfExists('venue_schedules');
        Schema::table('venues', fn (Blueprint $table) => $table->dropColumn(['geofence_enabled', 'latitude', 'longitude', 'geofence_radius_meters', 'manual_open_state', 'manual_open_until']));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['public_token', 'customer_name', 'customer_phone', 'whatsapp_consent']));
    }
};

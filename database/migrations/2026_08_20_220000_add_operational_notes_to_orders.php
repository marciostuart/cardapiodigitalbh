<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::table('orders', function (Blueprint $table) { $table->text('kitchen_notes')->nullable(); $table->text('delivery_notes')->nullable(); }); }
    public function down(): void { Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['kitchen_notes', 'delivery_notes'])); }
};

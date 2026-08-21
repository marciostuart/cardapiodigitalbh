<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->string('catalog_display_mode')->default('cards')->after('catalog_sort_mode');
        });
    }

    public function down(): void
    {
        Schema::table('venues', fn (Blueprint $table) => $table->dropColumn('catalog_display_mode'));
    }
};

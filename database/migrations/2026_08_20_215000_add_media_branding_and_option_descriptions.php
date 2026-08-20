<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $t) {
            $t->string('logo_path')->nullable();
            $t->string('primary_color', 7)->default('#087f5b');
            $t->string('secondary_color', 7)->default('#102b3c');
        });
        Schema::table('product_options', fn (Blueprint $t) => $t->string('description', 300)->nullable());
        Schema::create('venue_banners', function (Blueprint $t) {
            $t->id();
            $t->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $t->string('title', 120);
            $t->string('description', 300)->nullable();
            $t->string('image_path');
            $t->string('link_url', 500)->nullable();
            $t->unsignedInteger('position')->default(0);
            $t->boolean('active')->default(true);
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_banners');
        Schema::table('product_options', fn (Blueprint $t) => $t->dropColumn('description'));
        Schema::table('venues', fn (Blueprint $t) => $t->dropColumn(['logo_path', 'primary_color', 'secondary_color']));
    }
};

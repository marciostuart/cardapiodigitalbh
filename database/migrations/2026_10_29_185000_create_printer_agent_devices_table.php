<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('printer_agent_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->char('token_hash', 64)->unique();
            $table->boolean('is_primary')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'active', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('printer_agent_devices');
    }
};

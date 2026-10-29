<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('printer_agent_device_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 40)->default('kitchen_ticket');
            $table->string('job_key', 120)->unique();
            $table->longText('payload');
            $table->string('status', 24)->default('queued')->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('last_error', 255)->nullable();
            $table->string('printer_name', 120)->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_jobs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_schedule_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->boolean('is_closed')->default(true);
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->string('note', 120)->nullable();
            $table->timestamps();
            $table->unique(['venue_id', 'date']);
        });
    }

    public function down(): void { Schema::dropIfExists('venue_schedule_exceptions'); }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->string('catalog_sort_mode')->default('popularity')->after('timezone');
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('monthly_price', 12, 2)->default(0);
            $table->json('features')->nullable();
            $table->json('limits')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('tenant_feature_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('feature');
            $table->boolean('enabled');
            $table->json('value')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'feature']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'action']);
        });

        Schema::create('evolution_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('instance_name')->unique();
            $table->string('status')->default('disconnected');
            $table->string('phone')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->string('name');
            $table->text('body');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'event']);
        });

        Schema::create('outbound_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel')->default('whatsapp');
            $table->string('event');
            $table->string('recipient');
            $table->text('body');
            $table->string('status')->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->string('idempotency_key')->unique();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        $now = now();
        DB::table('plans')->insert([
            ['name' => 'Grátis', 'slug' => 'free', 'description' => 'Plano inicial para novos estabelecimentos.', 'monthly_price' => 0, 'features' => json_encode(['catalog' => true, 'tables' => true]), 'limits' => json_encode(['products' => 30]), 'active' => true, 'position' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Essencial', 'slug' => 'essencial', 'description' => 'Operação completa para pequenos estabelecimentos.', 'monthly_price' => 0, 'features' => json_encode(['catalog' => true, 'tables' => true, 'inventory' => true, 'delivery' => true]), 'limits' => json_encode([]), 'active' => true, 'position' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Pro', 'slug' => 'pro', 'description' => 'Automação, integrações e recursos avançados.', 'monthly_price' => 0, 'features' => json_encode(['catalog' => true, 'tables' => true, 'inventory' => true, 'delivery' => true, 'whatsapp' => true, 'printing' => true, 'reports' => true]), 'limits' => json_encode([]), 'active' => true, 'position' => 30, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('whatsapp_templates')->insert([
            ['tenant_id' => null, 'event' => 'order_received', 'name' => 'Pedido recebido', 'body' => "Olá, {{customer_name}}! Recebemos o pedido #{{order_number}} do {{venue_name}}.", 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['tenant_id' => null, 'event' => 'order_preparing', 'name' => 'Pedido em preparo', 'body' => "Seu pedido #{{order_number}} já está em preparo.", 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['tenant_id' => null, 'event' => 'order_ready', 'name' => 'Pedido pronto', 'body' => "Seu pedido #{{order_number}} está pronto!", 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['tenant_id' => null, 'event' => 'order_completed', 'name' => 'Pedido concluído', 'body' => "Pedido #{{order_number}} concluído. Obrigado por escolher o {{venue_name}}!", 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['tenant_id' => null, 'event' => 'order_cancelled', 'name' => 'Pedido cancelado', 'body' => "O pedido #{{order_number}} foi cancelado. Em caso de dúvida, fale com o {{venue_name}}.", 'active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_messages');
        Schema::dropIfExists('whatsapp_templates');
        Schema::dropIfExists('evolution_instances');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('tenant_feature_overrides');
        Schema::dropIfExists('plans');
        Schema::table('venues', fn (Blueprint $table) => $table->dropColumn('catalog_sort_mode'));
    }
};

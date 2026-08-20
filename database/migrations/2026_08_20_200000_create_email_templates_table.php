<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('subject');
            $table->string('heading');
            $table->text('body');
            $table->string('action_text')->nullable();
            $table->text('footer')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('email_templates')->insert([
            [
                'key' => 'welcome',
                'name' => 'Boas-vindas',
                'subject' => 'Seu Cardápio Digital BH está pronto',
                'heading' => 'Boas-vindas, {{name}}!',
                'body' => "O cadastro de {{venue_name}} foi concluído com sucesso no plano Grátis.\n\nAgora você já pode cadastrar produtos, criar mesas e imprimir os QR Codes do estabelecimento.",
                'action_text' => 'Acessar meu painel',
                'footer' => 'Conte com a gente para colocar sua operação no ar.',
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'password_reset',
                'name' => 'Recuperação de senha',
                'subject' => 'Redefina sua senha no Cardápio Digital BH',
                'heading' => 'Olá, {{name}}',
                'body' => "Recebemos uma solicitação para redefinir a senha da sua conta.\n\nEste link expira em {{expire_minutes}} minutos. Se você não fez esta solicitação, ignore esta mensagem.",
                'action_text' => 'Criar nova senha',
                'footer' => 'Sua segurança é importante para nós.',
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'email_verification',
                'name' => 'Verificação de e-mail',
                'subject' => 'Confirme seu e-mail no Cardápio Digital BH',
                'heading' => 'Confirme seu endereço de e-mail',
                'body' => 'Olá, {{name}}. Confirme seu endereço de e-mail para proteger sua conta e receber comunicações importantes da plataforma.',
                'action_text' => 'Confirmar meu e-mail',
                'footer' => 'Se você não criou esta conta, ignore esta mensagem.',
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};

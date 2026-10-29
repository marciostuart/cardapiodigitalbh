<?php

namespace Tests\Feature;

use Tests\TestCase;

class MarketingBrandExperienceTest extends TestCase
{
    public function test_marketing_mobile_menu_contains_panel_and_registration_actions(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('header-nav__actions', false)
            ->assertSee('Entrar no painel')
            ->assertSee('Criar cardápio grátis');
    }

    public function test_all_home_faq_items_start_collapsed(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('<details open>', false);
    }

    public function test_not_found_page_uses_the_brand_error_layout(): void
    {
        $this->get('/endereco-que-nao-existe')
            ->assertNotFound()
            ->assertSee('Cardápio Digital BH')
            ->assertSee('cardapio-digital-bh-brand.svg');
    }

    public function test_all_error_templates_use_the_brand_layout(): void
    {
        foreach (['403', '404', '419', '422', '429', '500', '503', '4xx', '5xx'] as $status) {
            $html = view('errors.'.$status)->render();

            $this->assertStringContainsString('cardapio-digital-bh-brand.svg', $html, $status);
            $this->assertStringContainsString('#ea4335', $html, $status);
        }
    }

    public function test_transactional_email_uses_the_brand_palette(): void
    {
        $html = view('emails.transactional', [
            'heading' => 'Confirme seu e-mail',
            'body' => 'Confirme sua conta para acessar o painel.',
            'actionText' => 'Confirmar e-mail',
            'actionUrl' => 'https://cardapiodigitalbh.com.br/verify-email/example',
            'footer' => 'Se você não solicitou esta conta, ignore esta mensagem.',
        ])->render();

        $this->assertStringContainsString('#ea4335', $html);
        $this->assertStringContainsString('#fbbc05', $html);
        $this->assertStringContainsString('CARDÁPIO DIGITAL', $html);
        $this->assertStringContainsString('Confirme seu e-mail', $html);
    }
}

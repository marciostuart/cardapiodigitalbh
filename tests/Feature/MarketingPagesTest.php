<?php

namespace Tests\Feature;

use Tests\TestCase;

class MarketingPagesTest extends TestCase
{
    public function test_indexed_marketing_and_legal_urls_remain_available(): void
    {
        $this->get('/')->assertOk()->assertSee('Cardápio Digital BH');
        $this->get('/cardapio-digital-em-belo-horizonte')->assertOk()->assertSee('cardápio digital em Belo Horizonte', false);
        $this->get('/politica-de-privacidade')->assertOk()->assertSee('Política de Privacidade');
        $this->get('/termos-de-uso')->assertOk()->assertSee('Termos de Uso');
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')->assertSee('<urlset', false);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\Request;

class PublicAgentController extends Controller
{
    public function tenant(Request $request, string $subdomain)
    {
        $venue = $request->attributes->get('venue');
        abort_unless($venue && $venue->subdomain === $subdomain, 404);

        $baseUrl = 'https://'.$request->getHttpHost();
        $categories = ProductCategory::where('venue_id', $venue->id)->where('active', true)->orderBy('position')->get(['id', 'name']);
        $lines = [
            '# '.$venue->name,
            '',
            '> Cardápio digital brasileiro em português. Produtos, preços e disponibilidade são administrados pelo estabelecimento.',
            '',
            '## Cardápio',
            '- [Página inicial]('.$baseUrl.'/): Cardápio, categorias, produtos, preços e formas de pedido.',
        ];
        foreach ($categories as $category) {
            $lines[] = '- ['.$category->name.']('.$baseUrl.'/#categoria-'.$category->id.'): Categoria do cardápio.';
        }
        $lines = array_merge($lines, ['', '## Orientações para agentes', '- Use os preços e a disponibilidade exibidos na página do cardápio como fonte atual.', '- Não envie pedidos, não aceite pagamentos e não altere dados sem confirmação explícita da pessoa usuária.', '- Para um pedido, direcione a pessoa ao cardápio para revisar itens, adicionais, endereço e pagamento.']);

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/markdown; charset=utf-8', 'Cache-Control' => 'public, max-age=300']);
    }

    public function platform()
    {
        return response("# Cardápio Digital BH\n\n> Plataforma brasileira de cardápios digitais para estabelecimentos.\n\n## Páginas\n- [Site institucional](https://cardapiodigitalbh.com.br/): Conheça a plataforma.\n- [Cadastro](https://cardapiodigitalbh.com.br/cadastrar): Crie uma conta.\n- [Login](https://cardapiodigitalbh.com.br/login): Acesse o painel.\n\n## Orientações para agentes\n- Cada estabelecimento possui seu próprio subdomínio em `slug.cardapiodigitalbh.com.br`.\n- Não realize ações transacionais sem confirmação explícita da pessoa usuária.\n", 200, ['Content-Type' => 'text/markdown; charset=utf-8', 'Cache-Control' => 'public, max-age=3600']);
    }
}

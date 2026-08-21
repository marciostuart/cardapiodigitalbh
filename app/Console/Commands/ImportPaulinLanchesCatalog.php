<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\Venue;
use App\Services\TenantMedia;
use DOMDocument;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ImportPaulinLanchesCatalog extends Command
{
    protected $signature = 'catalog:import-paulin-lanches {--dry-run : Apenas valida as páginas de origem}';

    protected $description = 'Importa, página a página, o catálogo autorizado do Paulin Lanches para o slug paulinlanches.';

    private const SOURCE = 'https://paulinlanches.pedidoturbo.com.br';

    private const CATALOG = [
        'Acréscimos/valor válido p/1 sanduíche' => [5352, 5351, 5132, 5131, 5130, 5129, 5128, 5127, 5126, 5125, 5124, 5123, 5122, 5121, 5120],
        'Promoções' => [5350, 5349, 5348, 5030, 5029, 5028, 5027, 5026, 5025, 5024, 5023],
        'Barca Mix' => [5032, 5031],
        'Hambúrgueres' => [5050, 5049, 5048, 5047, 5046, 5045, 5044, 5043, 5042, 5041, 5040, 5039, 5038, 5037, 5036, 5035, 5034, 5033],
        'Pratos e Massas' => [5075, 5074, 5073, 5072, 5071, 5052],
        'Porções (Peso In Natura)' => [5345, 5247, 5246, 5245, 5244, 5243, 5242, 5069, 5068, 5067, 5057, 5056, 5055, 5054, 5053],
        'Bebidas' => [5356, 5355, 5354, 5347, 5065, 5064, 5063, 5062, 5061, 5060, 5059],
        'Molhos especiais e sachês' => [5344, 5343, 5342, 5341, 5340],
    ];

    public function handle(TenantMedia $media): int
    {
        $venue = Venue::query()->with('tenant')->where('subdomain', 'paulinlanches')->first();
        if (! $venue || ! $venue->tenant instanceof Tenant) {
            $this->error('O estabelecimento paulinlanches não foi encontrado.');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $totals = ['products' => 0, 'images' => 0, 'options' => 0];

        foreach (self::CATALOG as $categoryName => $productIds) {
            $category = ProductCategory::firstOrCreate(
                ['venue_id' => $venue->id, 'name' => $categoryName],
                ['position' => (ProductCategory::where('venue_id', $venue->id)->max('position') ?? 0) + 10, 'active' => true],
            );

            foreach ($productIds as $position => $sourceId) {
                $data = $this->readProductPage($sourceId);
                $totals['products']++;
                $totals['images'] += count($data['images']);
                $totals['options'] += collect($data['groups'])->sum(fn (array $group) => count($group['options']));
                $this->line("[{$categoryName}] {$data['name']} (REF-{$sourceId})");

                if ($dryRun) {
                    continue;
                }

                $product = Product::query()
                    ->where('venue_id', $venue->id)
                    ->where('sku', $data['sku'])
                    ->first()
                    ?? Product::query()->where('venue_id', $venue->id)->where('product_category_id', $category->id)->where('name', $data['name'])->first()
                    ?? new Product(['venue_id' => $venue->id]);

                $product->fill([
                    'product_category_id' => $category->id,
                    'name' => $data['name'],
                    'sku' => $data['sku'],
                    'description' => $data['description'],
                    'price' => $data['price'],
                    'station' => 'kitchen',
                    'active' => true,
                    'position' => ($position + 1) * 10,
                ]);

                if ($data['images']) {
                    $firstImage = array_shift($data['images']);
                    $newPath = $this->storeImage($venue->tenant, $media, $firstImage, $venue->name.' '.$data['name']);
                    if ($newPath) {
                        $media->delete($product->image_path, $venue->tenant);
                        $product->image_path = $newPath;
                    }
                }

                $product->save();
                foreach ($product->images as $image) {
                    $media->delete($image->path, $venue->tenant);
                    $image->delete();
                }
                foreach (array_slice($data['images'], 0, 4) as $imagePosition => $imageUrl) {
                    if ($path = $this->storeImage($venue->tenant, $media, $imageUrl, $venue->name.' '.$data['name'].' galeria '.($imagePosition + 1))) {
                        $product->images()->create(['path' => $path, 'position' => $imagePosition + 1]);
                    }
                }
                foreach ($product->optionGroups as $group) {
                    $group->delete();
                }
                foreach ($data['groups'] as $groupPosition => $groupData) {
                    $group = $product->optionGroups()->create([
                        'name' => $groupData['name'], 'minimum' => $groupData['minimum'], 'maximum' => $groupData['maximum'],
                        'position' => ($groupPosition + 1) * 10, 'active' => true,
                    ]);
                    foreach ($groupData['options'] as $optionPosition => $optionData) {
                        $group->options()->create($optionData + ['position' => ($optionPosition + 1) * 10, 'active' => true]);
                    }
                }
            }
        }

        $this->info("Conferência concluída: {$totals['products']} produtos, {$totals['images']} imagens e {$totals['options']} opções.");
        return self::SUCCESS;
    }

    private function readProductPage(int $sourceId): array
    {
        $response = Http::timeout(30)->retry(2, 500)->get(self::SOURCE.'/produto/'.$sourceId);
        if (! $response->successful()) {
            throw new \RuntimeException("Não foi possível ler a página do produto {$sourceId}.");
        }
        $html = $response->body();
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new DOMXPath($document);
        $text = static fn ($node): string => trim(preg_replace('/\s+/u', ' ', html_entity_decode($node?->textContent ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        $meta = $xpath->query('//meta[@property="og:title"]')->item(0)?->getAttribute('content') ?? '';
        $name = trim(preg_replace('/\s*-\s*pedidoturbo\.com\.br!?$/iu', '', preg_replace('/^Paulin Lanches\s*-\s*/u', '', $meta)));
        if ($name === '') {
            throw new \RuntimeException("Nome não encontrado na página do produto {$sourceId}.");
        }
        $descriptionNode = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " descricao ")]/span')->item(0);
        $valueNodes = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " valor ")]/span');
        $price = 0.0;
        foreach ($valueNodes as $valueNode) {
            if (preg_match('/R\$\s*([\d\.]+,[\d]{2})/u', $text($valueNode), $match)) {
                $price = (float) str_replace(['.', ','], ['', '.'], $match[1]);
                break;
            }
        }
        $images = [];
        foreach ($xpath->query('//a[@data-fancybox="galeria"]/@href') as $image) {
            $images[] = $image->nodeValue;
        }
        $groups = [];
        foreach ($xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " line-variacao ")]') as $groupNode) {
            $groupName = $text($xpath->query('.//span[contains(concat(" ", normalize-space(@class), " "), " thelabel ")]', $groupNode)->item(0));
            if ($groupName === '') {
                continue;
            }
            $options = [];
            foreach ($xpath->query('.//div[contains(concat(" ", normalize-space(@class), " "), " opcao ")]', $groupNode) as $optionNode) {
                $optionName = $text($xpath->query('.//span[contains(concat(" ", normalize-space(@class), " "), " titulo ")]', $optionNode)->item(0));
                $optionName = trim(preg_replace('/\s*\(\+\s*R\$.*$/u', '', $optionName));
                if ($optionName === '') {
                    continue;
                }
                $options[] = ['name' => $optionName, 'description' => $text($xpath->query('.//span[contains(concat(" ", normalize-space(@class), " "), " descricao ")]', $optionNode)->item(0)) ?: null, 'price' => (float) ($optionNode->getAttribute('valor-adicional') ?: 0)];
            }
            if ($options) {
                $groups[] = ['name' => $groupName, 'minimum' => (int) $groupNode->getAttribute('min'), 'maximum' => max(1, (int) $groupNode->getAttribute('max')), 'options' => $options];
            }
        }

        return ['sku' => 'REF-'.$sourceId, 'name' => $name, 'description' => $text($descriptionNode) ?: null, 'price' => $price, 'images' => array_values(array_unique($images)), 'groups' => $groups];
    }

    private function storeImage(Tenant $tenant, TenantMedia $media, string $url, string $seoName): ?string
    {
        $response = Http::timeout(30)->retry(2, 500)->get($url);
        if (! $response->successful() || $response->body() === '') {
            $this->warn("Imagem indisponível: {$url}");
            return null;
        }
        $temporaryPath = tempnam(sys_get_temp_dir(), 'paulin-image-');
        file_put_contents($temporaryPath, $response->body());
        try {
            return $media->store($tenant, new UploadedFile($temporaryPath, basename(parse_url($url, PHP_URL_PATH)) ?: Str::slug($seoName).'.jpg', $response->header('Content-Type') ?: 'image/jpeg', null, true), 'products', $seoName);
        } finally {
            @unlink($temporaryPath);
        }
    }
}

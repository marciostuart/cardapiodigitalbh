<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicPwaController extends Controller
{
    public function manifest(Request $request, string $subdomain)
    {
        $venue = $request->attributes->get('venue');
        abort_unless($venue, 404);

        $logo = $venue->logo_path ? route('public.media', ['subdomain' => $venue->subdomain, 'category' => 'logos', 'filename' => basename($venue->logo_path)]) : null;
        $manifest = [
            'name' => $venue->name.' | Cardápio Digital',
            'short_name' => str($venue->name)->limit(24, '')->toString(),
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => $venue->secondary_color ?: '#102b3c',
            'theme_color' => $venue->primary_color ?: '#087f5b',
            'description' => ($venue->settings['seo_description'] ?? null) ?: 'Cardápio digital de '.$venue->name,
            'icons' => $logo ? [['src' => $logo, 'sizes' => 'any', 'type' => 'image/webp', 'purpose' => 'any maskable']] : [],
        ];

        return response()->json($manifest, 200, ['Cache-Control' => 'no-cache, max-age=0']);
    }

    public function serviceWorker(Request $request, string $subdomain)
    {
        $venue = $request->attributes->get('venue');
        abort_unless($venue, 404);
        $revision = Cache::remember('pwa-revision:'.$venue->id, now()->addMinute(), function () use ($venue) {
            return implode('-', array_filter([
                $venue->updated_at?->getTimestamp(),
                strtotime((string) Product::where('venue_id', $venue->id)->max('updated_at')) ?: null,
                strtotime((string) ProductCategory::where('venue_id', $venue->id)->max('updated_at')) ?: null,
                strtotime((string) $venue->banners()->max('updated_at')) ?: null,
            ]));
        });
        $cacheName = 'cardapio-'.$venue->id.'-'.$revision;
        $script = <<<JS
const CACHE_NAME = {$this->json($cacheName)};
const HOME_URL = '/';
self.addEventListener('install', event => { event.waitUntil(caches.open(CACHE_NAME).then(cache => cache.add(HOME_URL))); });
self.addEventListener('activate', event => { event.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(key => key.startsWith('cardapio-') && key !== CACHE_NAME).map(key => caches.delete(key)))).then(() => self.clients.claim())); });
self.addEventListener('message', event => { if (event.data && event.data.type === 'ATIVAR_ATUALIZACAO') self.skipWaiting(); });
self.addEventListener('fetch', event => {
  const request = event.request;
  if (request.method !== 'GET' || new URL(request.url).origin !== self.location.origin) return;
  if (request.destination === 'image' || new URL(request.url).pathname.startsWith('/media/')) {
    event.respondWith(caches.match(request).then(cached => cached || fetch(request).then(response => { const copy = response.clone(); caches.open(CACHE_NAME).then(cache => cache.put(request, copy)); return response; }))); return;
  }
  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).then(response => { const copy = response.clone(); caches.open(CACHE_NAME).then(cache => cache.put(request, copy)); return response; }).catch(() => caches.match(request).then(cached => cached || caches.match(HOME_URL))));
  }
});
JS;

        return response($script, 200, ['Content-Type' => 'application/javascript; charset=utf-8', 'Cache-Control' => 'no-cache, no-store, must-revalidate', 'Service-Worker-Allowed' => '/']);
    }

    private function json(string $value): string
    {
        return json_encode($value, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG);
    }
}

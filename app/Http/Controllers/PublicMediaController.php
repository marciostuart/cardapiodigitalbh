<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PublicMediaController extends Controller
{
    public function __invoke(Request $request, string $subdomain, string $category, string $filename)
    {
        abort_unless(in_array($category, ['logos', 'products', 'banners', 'seo'], true) && preg_match('/^[a-z0-9][a-z0-9._-]+$/', $filename), 404, 'Nome de mídia inválido.');
        $venue = $request->attributes->get('venue');
        abort_unless($venue, 404, 'Estabelecimento da mídia não encontrado.');
        $path = "tenants/{$venue->tenant_id}/{$category}/{$filename}";
        $disk = Storage::disk(config('filesystems.media_disk', 's3'));
        $stream = $disk->readStream($path);
        abort_unless(is_resource($stream), 404, 'Arquivo de mídia não encontrado.');
        $contentType = match (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            default => 'image/webp',
        };

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            fclose($stream);
        }, 200, ['Content-Type' => $contentType, 'Cache-Control' => 'public, max-age=604800, immutable']);
    }
}

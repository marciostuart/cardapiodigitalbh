<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PublicMediaController extends Controller
{
    public function __invoke(Request $request, string $category, string $filename)
    {
        abort_unless(in_array($category, ['logos', 'products', 'banners'], true) && preg_match('/^[a-z0-9][a-z0-9._-]+$/', $filename), 404);
        $venue = $request->attributes->get('venue');
        abort_unless($venue, 404);
        $path = "tenants/{$venue->tenant_id}/{$category}/{$filename}";
        $disk = Storage::disk(config('filesystems.media_disk', 's3'));
        abort_unless($disk->exists($path), 404);

        return response()->stream(fn () => fpassthru($disk->readStream($path)), 200, ['Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream', 'Cache-Control' => 'public, max-age=604800, immutable']);
    }
}

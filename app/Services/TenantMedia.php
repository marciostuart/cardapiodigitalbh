<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TenantMedia
{
    private const CATEGORIES = ['logos', 'products', 'banners'];

    public function store(Tenant $tenant, UploadedFile $file, string $category, string $seoName): string
    {
        abort_unless(in_array($category, self::CATEGORIES, true), 422);
        $base = Str::slug($seoName) ?: 'imagem';
        $extension = strtolower($file->guessExtension() ?: $file->extension() ?: 'webp');
        $filename = Str::limit($base, 90, '').'-'.Str::lower(Str::random(8)).'.'.$extension;
        $path = "tenants/{$tenant->id}/{$category}/{$filename}";
        $written = Storage::disk(config('filesystems.media_disk', 's3'))->putFileAs(dirname($path), $file, basename($path));
        abort_unless($written, 500, 'Não foi possível armazenar a imagem.');

        return $path;
    }

    public function delete(?string $path, Tenant $tenant): void
    {
        if ($path && str_starts_with($path, "tenants/{$tenant->id}/")) {
            Storage::disk(config('filesystems.media_disk', 's3'))->delete($path);
        }
    }
}

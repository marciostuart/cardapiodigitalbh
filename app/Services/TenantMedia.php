<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TenantMedia
{
    private const CATEGORIES = ['logos', 'products', 'banners', 'seo'];

    public function __construct(private readonly ImageOptimizer $optimizer) {}

    public function store(Tenant $tenant, UploadedFile $file, string $category, string $seoName): string
    {
        abort_unless(in_array($category, self::CATEGORIES, true), 422);
        $base = Str::slug($seoName) ?: 'imagem';
        $filename = Str::limit($base, 90, '').'-'.Str::lower(Str::random(8)).'.webp';
        $path = "tenants/{$tenant->id}/{$category}/{$filename}";
        $optimized = $this->optimizer->toWebp($file);
        $stream = fopen($optimized, 'rb');

        try {
            $written = Storage::disk(config('filesystems.media_disk', 's3'))->put($path, $stream, [
                'ContentType' => 'image/webp',
            ]);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }

            @unlink($optimized);
        }

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

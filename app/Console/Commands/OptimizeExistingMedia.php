<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Venue;
use App\Models\VenueBanner;
use App\Services\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class OptimizeExistingMedia extends Command
{
    protected $signature = 'media:optimize-existing {--tenant= : Limita a um tenant}';

    protected $description = 'Reprocessa mídias WebP existentes com a otimização atual';

    public function handle(ImageOptimizer $optimizer): int
    {
        $tenantId = $this->option('tenant');
        $paths = collect()
            ->merge(Product::query()->when($tenantId, fn ($query) => $query->whereHas('venue', fn ($venue) => $venue->where('tenant_id', $tenantId)))->pluck('image_path'))
            ->merge(ProductImage::query()->when($tenantId, fn ($query) => $query->whereHas('product.venue', fn ($venue) => $venue->where('tenant_id', $tenantId)))->pluck('path'))
            ->merge(Venue::query()->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))->pluck('logo_path'))
            ->merge(VenueBanner::query()->when($tenantId, fn ($query) => $query->whereHas('venue', fn ($venue) => $venue->where('tenant_id', $tenantId)))->pluck('image_path'))
            ->filter(fn ($path) => is_string($path) && str_ends_with(strtolower($path), '.webp'))
            ->unique();

        $disk = Storage::disk(config('filesystems.media_disk', 's3'));
        $optimizedCount = 0;
        foreach ($paths as $path) {
            $source = tempnam(sys_get_temp_dir(), 'cdbh-existing-');
            $input = $disk->readStream($path);
            if (! is_resource($input) || ! $source) {
                $this->warn("Ignorada: {$path}");

                continue;
            }

            $outputFile = fopen($source, 'wb');
            stream_copy_to_stream($input, $outputFile);
            fclose($input);
            fclose($outputFile);

            $optimized = $optimizer->toWebp(new UploadedFile($source, basename($path), 'image/webp', null, true));
            $stream = fopen($optimized, 'rb');
            $written = $disk->put($path, $stream, ['ContentType' => 'image/webp']);
            fclose($stream);
            @unlink($source);
            @unlink($optimized);

            if ($written) {
                $optimizedCount++;
            }
        }

        $this->info("{$optimizedCount} mídia(s) otimizada(s).");

        return self::SUCCESS;
    }
}

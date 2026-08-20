<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class ImageOptimizer
{
    private const MAX_SIDE = 1400;

    private const MAX_PIXELS = 24_000_000;

    private const WEBP_QUALITY = 68;

    private const MIN_WEBP_QUALITY = 55;

    private const TARGET_BYTES = 140 * 1024;

    public function toWebp(UploadedFile $file): string
    {
        abort_unless(function_exists('imagewebp'), 500, 'O servidor não possui suporte para otimização WebP.');

        $info = @getimagesize($file->getRealPath());
        if (! $info || $info[0] < 1 || $info[1] < 1 || ($info[0] * $info[1]) > self::MAX_PIXELS) {
            throw ValidationException::withMessages(['image' => 'A imagem é inválida ou possui dimensões excessivas.']);
        }

        $contents = file_get_contents($file->getRealPath());
        $source = $contents === false ? false : @imagecreatefromstring($contents);
        if (! $source) {
            throw ValidationException::withMessages(['image' => 'Não foi possível processar esta imagem.']);
        }

        $source = $this->orient($source, $file);
        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, self::MAX_SIDE / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagefill($target, 0, 0, imagecolorallocatealpha($target, 255, 255, 255, 127));
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        $path = tempnam(sys_get_temp_dir(), 'cdbh-webp-');
        $quality = self::WEBP_QUALITY;
        $saved = $path && imagewebp($target, $path, $quality);
        clearstatcache(true, $path ?: '');
        while ($saved && filesize($path) > self::TARGET_BYTES && $quality > self::MIN_WEBP_QUALITY) {
            $quality = max(self::MIN_WEBP_QUALITY, $quality - 5);
            $saved = imagewebp($target, $path, $quality);
            clearstatcache(true, $path);
        }

        imagedestroy($source);
        imagedestroy($target);

        if (! $saved) {
            if ($path) {
                @unlink($path);
            }

            throw ValidationException::withMessages(['image' => 'Não foi possível otimizar esta imagem.']);
        }

        return $path;
    }

    private function orient(\GdImage $image, UploadedFile $file): \GdImage
    {
        if ($file->getMimeType() !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = @exif_read_data($file->getRealPath())['Orientation'] ?? 1;
        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => false,
        };

        if ($rotated) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }
}

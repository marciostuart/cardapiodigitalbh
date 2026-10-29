#!/bin/sh
set -eu
app=$(docker ps --filter name=cardapiodigitalbh_app --format '{{.ID}}' | head -n1)
test -n "$app"
docker exec "$app" php artisan tinker --execute='
$source = tempnam(sys_get_temp_dir(), "cdbh-source-");
throw_unless($source !== false, RuntimeException::class, "Falha ao criar imagem temporaria.");
$output = null;
$storage = null;
$key = "health/optimized-check-" . bin2hex(random_bytes(16)) . ".webp";
$failure = null;
$result = null;

try {
    $storage = Illuminate\Support\Facades\Storage::disk("s3");
    $image = imagecreatetruecolor(2000, 1000);
    throw_unless($image !== false, RuntimeException::class, "Falha ao criar imagem de teste.");
    try {
        imagefill($image, 0, 0, imagecolorallocate($image, 20, 140, 90));
        throw_unless(imagepng($image, $source), RuntimeException::class, "Falha ao gravar imagem de teste.");
    } finally {
        imagedestroy($image);
    }

    $upload = new Illuminate\Http\UploadedFile($source, "teste.png", "image/png", null, true);
    $output = app(App\Services\ImageOptimizer::class)->toWebp($upload);
    $info = getimagesize($output);
    throw_unless($info !== false && $info[0] === 1400 && $info[1] === 700 && $info["mime"] === "image/webp", RuntimeException::class, "Falha na otimizacao WebP.");
    $bytes = filesize($output);
    $stream = fopen($output, "rb");
    throw_unless($stream !== false, RuntimeException::class, "Falha ao ler WebP temporario.");
    try {
        throw_unless($storage->put($key, $stream, ["ContentType" => "image/webp"]), RuntimeException::class, "Falha no MinIO ao enviar objeto de teste.");
    } finally {
        fclose($stream);
    }
    throw_unless($storage->exists($key), RuntimeException::class, "Falha no MinIO ao confirmar objeto de teste.");
    $result = ["dimensions" => "1400x700", "mime" => $info["mime"], "bytes" => $bytes, "storage" => "ok"];
} catch (\Throwable $error) {
    $failure = $error;
} finally {
    if ($storage !== null) {
        try {
            $storage->delete($key);
            throw_unless(!$storage->exists($key), RuntimeException::class, "Falha ao remover objeto temporario do MinIO: " . $key);
        } catch (\Throwable $cleanupError) {
            if ($failure === null) $failure = $cleanupError;
        }
    }
    @unlink($source);
    if (is_string($output)) @unlink($output);
}

if ($failure !== null) throw $failure;
dump($result);
'
if docker service ls | grep -q cdbh_minio_setup; then echo 'Serviço temporário não removido.'; exit 1; fi

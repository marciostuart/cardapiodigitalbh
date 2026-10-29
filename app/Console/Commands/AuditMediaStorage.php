<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class AuditMediaStorage extends Command
{
    protected $signature = 'media:audit {--prefix=tenants : Prefixo permitido para auditoria}';

    protected $description = 'Mede quantidade e volume de mídias no MinIO sem alterar ou remover objetos';

    public function handle(): int
    {
        $prefix = trim((string) $this->option('prefix'), '/');
        if (! in_array($prefix, ['tenants', 'health'], true)) {
            $this->error('O prefixo precisa ser tenants ou health.');

            return self::FAILURE;
        }

        $disk = Storage::disk(config('filesystems.media_disk', 's3'));
        $count = 0;
        $bytes = 0;
        $newest = null;
        $oldest = null;

        foreach ($disk->allFiles($prefix) as $path) {
            $count++;
            try {
                $size = $disk->size($path);
                $modified = $disk->lastModified($path);
            } catch (\Throwable $exception) {
                $this->warn("Não foi possível ler metadados de {$path}: {$exception->getMessage()}");

                continue;
            }

            $bytes += $size;
            $newest = $newest === null ? $modified : max($newest, $modified);
            $oldest = $oldest === null ? $modified : min($oldest, $modified);
        }

        $this->table(['Campo', 'Valor'], [
            ['Disco', config('filesystems.media_disk', 's3')],
            ['Prefixo', $prefix],
            ['Objetos', $count],
            ['Bytes', $bytes],
            ['Tamanho legível', $this->formatBytes($bytes)],
            ['Objeto mais antigo (UTC)', $oldest ? gmdate(DATE_ATOM, $oldest) : '—'],
            ['Objeto mais recente (UTC)', $newest ? gmdate(DATE_ATOM, $newest) : '—'],
            ['Modo', 'somente leitura; nenhuma exclusão'],
        ]);

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = (float) $bytes;
        $unit = 0;

        while ($value >= 1024 && $unit < count($units) - 1) {
            $value /= 1024;
            $unit++;
        }

        return number_format($value, 2, ',', '.').' '.$units[$unit];
    }
}

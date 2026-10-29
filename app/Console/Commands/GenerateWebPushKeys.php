<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateWebPushKeys extends Command
{
    protected $signature = 'webpush:keys {--env-file= : Caminho do arquivo .env que receberá as chaves}';

    protected $description = 'Gera e grava chaves VAPID no .env sem exibi-las no terminal';

    public function handle(): int
    {
        $envPath = $this->option('env-file') ?: base_path('.env');
        if (! is_file($envPath) || ! is_writable($envPath)) {
            $this->error('Execute no diretório de implantação onde o arquivo .env existe e pode ser gravado.');

            return self::FAILURE;
        }

        $contents = file_get_contents($envPath);
        if ($contents === false) {
            $this->error('Não foi possível ler o arquivo .env.');

            return self::FAILURE;
        }
        $original = $contents;

        foreach (['VAPID_PUBLIC_KEY', 'VAPID_PRIVATE_KEY'] as $key) {
            $matches = [];
            $count = preg_match_all('/^'.preg_quote($key, '/').'=(.*)$/m', $contents, $matches);
            if ($count > 1 || ($count === 1 && trim($matches[1][0]) !== '')) {
                $this->error('Existing VAPID keys or duplicate lines found. Nothing was changed.');

                return self::FAILURE;
            }
        }

        $keys = VAPID::createVapidKeys();
        $values = [
            'VAPID_PUBLIC_KEY' => $keys['publicKey'],
            'VAPID_PRIVATE_KEY' => $keys['privateKey'],
        ];
        $additions = '';
        foreach ($values as $key => $value) {
            $pattern = '/^'.preg_quote($key, '/').'=[ \t]*$/m';
            if (preg_match($pattern, $contents)) {
                $contents = preg_replace($pattern, $key.'='.$value, $contents, 1);
            } else {
                $additions .= $key.'='.$value."\n";
            }
        }
        if (preg_match('/^VAPID_SUBJECT=[ \t]*$/m', $contents)) {
            $contents = preg_replace('/^VAPID_SUBJECT=[ \t]*$/m', 'VAPID_SUBJECT=mailto:falecom@cardapiodigitalbh.com.br', $contents, 1);
        } elseif (! preg_match('/^VAPID_SUBJECT=.+$/m', $contents)) {
            $additions .= "VAPID_SUBJECT=mailto:falecom@cardapiodigitalbh.com.br\n";
        }
        $contents = rtrim($contents)."\n".$additions;
        $mode = fileperms($envPath) & 0777;

        $written = file_put_contents($envPath, $contents, LOCK_EX);
        if ($written !== strlen($contents) || ! chmod($envPath, $mode)) {
            file_put_contents($envPath, $original, LOCK_EX);
            chmod($envPath, $mode);
            $this->error('Não foi possível atualizar o .env. Nenhuma chave foi exibida.');

            return self::FAILURE;
        }

        $this->info('Chaves VAPID gravadas no .env sem exibir a chave privada. Preserve este par para manter as inscrições existentes.');

        return self::SUCCESS;
    }
}

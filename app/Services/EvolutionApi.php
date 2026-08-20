<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class EvolutionApi
{
    private function client(): PendingRequest
    {
        $url = rtrim((string) config('services.evolution.url'), '/');
        $key = (string) config('services.evolution.key');
        if ($url === '' || $key === '') {
            throw new RuntimeException('Evolution API não configurada no ambiente.');
        }
        return Http::baseUrl($url)->acceptJson()->asJson()->withHeaders(['apikey' => $key])->timeout(20)->retry(2, 400, throw: false);
    }

    public function create(string $instanceName): array
    {
        return $this->client()->post('/instance/create', ['instanceName' => $instanceName, 'qrcode' => true, 'integration' => 'WHATSAPP-BAILEYS'])->throw()->json();
    }

    public function connect(string $instanceName): array
    {
        return $this->client()->get('/instance/connect/'.rawurlencode($instanceName))->throw()->json();
    }

    public function connectionState(string $instanceName): array
    {
        return $this->client()->get('/instance/connectionState/'.rawurlencode($instanceName))->throw()->json();
    }

    public function delete(string $instanceName): void
    {
        $response = $this->client()->delete('/instance/delete/'.rawurlencode($instanceName));
        if (! $response->successful() && $response->status() !== 404) {
            $response->throw();
        }
    }

    public function sendText(string $instanceName, string $number, string $text): array
    {
        return $this->client()->post('/message/sendText/'.rawurlencode($instanceName), ['number' => $number, 'text' => $text])->throw()->json();
    }

    public static function qrCode(array $payload): ?string
    {
        return data_get($payload, 'qrcode.base64') ?? data_get($payload, 'base64') ?? data_get($payload, 'qrcode.code');
    }

    public static function state(array $payload): string
    {
        $state = strtolower((string) (data_get($payload, 'instance.state') ?? data_get($payload, 'state') ?? 'disconnected'));
        return in_array($state, ['open', 'connected'], true) ? 'connected' : (in_array($state, ['connecting'], true) ? 'connecting' : 'disconnected');
    }
}

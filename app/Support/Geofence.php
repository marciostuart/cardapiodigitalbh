<?php

namespace App\Support;

use App\Models\DiningTable;
use App\Models\GeofenceOverride;
use App\Models\Venue;

class Geofence
{
    public static function assertInside(Venue $venue, DiningTable $table, ?float $latitude, ?float $longitude, ?string $accessCode = null): ?GeofenceOverride
    {
        if (! $venue->geofence_enabled) {
            return null;
        }

        abort_if($venue->latitude === null || $venue->longitude === null, 503, 'A localização do estabelecimento ainda não foi configurada.');

        if ($accessCode !== null) {
            $override = GeofenceOverride::query()
                ->where('tenant_id', $venue->tenant_id)
                ->where('venue_id', $venue->id)
                ->where('dining_table_id', $table->id)
                ->where('code_hash', hash_hmac('sha256', $table->id.'|'.$accessCode, (string) config('app.key')))
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            abort_unless($override, 403, 'O código temporário é inválido, expirou ou já foi utilizado.');
            $override->update(['used_at' => now()]);

            return $override;
        }

        abort_if($latitude === null || $longitude === null, 422, 'Autorize a localização ou informe o código temporário fornecido pela equipe.');
        $distance = self::distance((float) $venue->latitude, (float) $venue->longitude, $latitude, $longitude);
        abort_if($distance > (int) $venue->geofence_radius_meters, 403, 'Você precisa estar dentro do estabelecimento ou usar um código temporário válido.');

        return null;
    }

    public static function distance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earth = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);
        $a = sin($latDelta / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;

        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}

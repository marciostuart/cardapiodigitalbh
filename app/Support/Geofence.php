<?php

namespace App\Support;

use App\Models\Venue;

class Geofence
{
    public static function assertInside(Venue $venue, ?float $latitude, ?float $longitude): void
    {
        if (! $venue->geofence_enabled) return;
        abort_if($venue->latitude === null || $venue->longitude === null, 503, 'A localização do estabelecimento ainda não foi configurada.');
        abort_if($latitude === null || $longitude === null, 422, 'Autorize a localização para realizar pedidos nesta mesa.');
        $distance = self::distance((float) $venue->latitude, (float) $venue->longitude, $latitude, $longitude);
        abort_if($distance > (int) $venue->geofence_radius_meters, 403, 'Você precisa estar dentro do estabelecimento para pedir por esta mesa.');
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

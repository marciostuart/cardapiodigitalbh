<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PlatformSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function value(string $key): ?string
    {
        return Cache::remember("platform-setting:{$key}", now()->addMinutes(10), fn () => static::query()->where('key', $key)->value('value'));
    }

    public static function putValue(string $key, ?string $value): void
    {
        if ($value === null || $value === '') {
            static::query()->where('key', $key)->delete();
        } else {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget("platform-setting:{$key}");
    }
}

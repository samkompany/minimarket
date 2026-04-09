<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Récupère un paramètre par sa clé, avec cache 1 heure.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            return Cache::remember("app_setting_{$key}", 3600, function () use ($key, $default): mixed {
                $value = static::where('key', $key)->value('value');

                return $value ?? $default;
            });
        } catch (\Exception $e) {
            // Si la table n'existe pas encore (ex: avant migration)
            return $default;
        }
    }

    /**
     * Enregistre ou met à jour un paramètre et vide le cache associé.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("app_setting_{$key}");
    }
}

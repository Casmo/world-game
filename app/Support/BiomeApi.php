<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

/**
 * Resolves a Tile's biome/terrain/resources from a third-party geographic
 * (land-cover) API. The provider is configurable (config/services.php 'geo') and
 * swappable behind this client (ADR-0008).
 */
class BiomeApi
{
    /**
     * @return array{biome: ?string, terrain: ?string, base_resources: array<string, mixed>}
     */
    public function resolve(float $lat, float $lng): array
    {
        $data = Http::get((string) config('services.geo.url'), [
            'lat' => $lat,
            'lng' => $lng,
        ])->throw()->json();

        return [
            'biome' => $data['biome'] ?? null,
            'terrain' => $data['terrain'] ?? null,
            'base_resources' => $data['base_resources'] ?? [],
        ];
    }
}

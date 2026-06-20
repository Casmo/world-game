<?php

namespace Database\Factories;

use App\Enums\TileResolutionStatus;
use App\Models\Tile;
use App\Support\H3;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tile>
 */
class TileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'h3_index' => app(H3::class)->latLngToCell(
                fake()->latitude(),
                fake()->longitude(),
                config('h3.resolution'),
            ),
            'biome' => null,
            'terrain' => null,
            'base_resources' => null,
            'resolution_status' => TileResolutionStatus::Pending,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn () => [
            'biome' => 'forest',
            'terrain' => 'hills',
            'base_resources' => ['wood' => 5],
            'resolution_status' => TileResolutionStatus::Resolved,
        ]);
    }
}

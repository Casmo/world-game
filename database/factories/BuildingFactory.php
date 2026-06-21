<?php

namespace Database\Factories;

use App\Enums\BuildingState;
use App\Enums\BuildingType;
use App\Models\Building;
use App\Models\Tile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Building>
 */
class BuildingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tile_id' => Tile::factory(),
            'plot_x' => fake()->numberBetween(0, Building::PLOT_GRID - 1),
            'plot_y' => fake()->numberBetween(0, Building::PLOT_GRID - 1),
            'type' => fake()->randomElement(BuildingType::cases()),
            'state' => BuildingState::UnderConstruction,
            'work_done' => 0,
        ];
    }

    public function ofType(BuildingType $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }

    public function built(): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => BuildingState::Built,
            'work_done' => ($attributes['type'] ?? BuildingType::Farm)->constructionWork(),
            'built_at' => now(),
        ]);
    }
}

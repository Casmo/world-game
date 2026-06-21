<?php

namespace App\Support;

use App\Enums\ResourceType;

/**
 * The NPC world market's price catalogue (ADR-0006): each Resource has a low
 * floor price (what the NPC pays the Team when it sells) and a higher ceiling
 * price (what the Team pays the NPC when it buys). Player-to-player trade later
 * lives in the spread between the two.
 */
class MarketCatalogue
{
    /**
     * The price the NPC pays the Team per unit when the Team sells (floor).
     */
    public function floor(ResourceType $type): int
    {
        return (int) config("market.prices.{$type->value}.floor");
    }

    /**
     * The price the Team pays the NPC per unit when the Team buys (ceiling).
     */
    public function ceiling(ResourceType $type): int
    {
        return (int) config("market.prices.{$type->value}.ceiling");
    }

    /**
     * The whole catalogue as view data: every Resource with its floor and ceiling.
     *
     * @return array<int, array{type: string, label: string, floor: int, ceiling: int}>
     */
    public function all(): array
    {
        return array_map(fn (ResourceType $type): array => [
            'type' => $type->value,
            'label' => $type->label(),
            'floor' => $this->floor($type),
            'ceiling' => $this->ceiling($type),
        ], ResourceType::cases());
    }
}

<?php

namespace App\Actions\Market;

use App\Enums\ResourceType;
use App\Events\TreasuryChanged;
use App\Exceptions\InsufficientResourcesException;
use App\Models\Team;
use App\Support\MarketCatalogue;

/**
 * Sell a Team's Resources to the NPC world market: the stockpile drops and the
 * treasury is credited at the floor price (creating Money — the economy's
 * faucet, ADR-0006).
 */
class SellToMarket
{
    public function __construct(private MarketCatalogue $catalogue) {}

    /**
     * @throws InsufficientResourcesException
     */
    public function handle(Team $team, ResourceType $type, int $quantity): void
    {
        if (! $team->removeResource($type, $quantity)) {
            throw new InsufficientResourcesException;
        }

        $team->depositTreasury($this->catalogue->floor($type) * $quantity);

        TreasuryChanged::dispatch($team->refresh());
    }
}

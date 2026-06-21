<?php

namespace App\Actions\Market;

use App\Enums\ResourceType;
use App\Events\TreasuryChanged;
use App\Exceptions\InsufficientTreasuryException;
use App\Models\Team;
use App\Support\MarketCatalogue;

/**
 * Buy Resources from the NPC world market: the treasury is debited at the
 * ceiling price (destroying Money — the economy's sink, ADR-0006) and the
 * stockpile grows. The debit is atomic and never lets the treasury go negative.
 */
class BuyFromMarket
{
    public function __construct(private MarketCatalogue $catalogue) {}

    /**
     * @throws InsufficientTreasuryException
     */
    public function handle(Team $team, ResourceType $type, int $quantity): void
    {
        $cost = $this->catalogue->ceiling($type) * $quantity;

        if (! $team->withdrawTreasury($cost)) {
            throw new InsufficientTreasuryException;
        }

        $team->addResource($type, $quantity);

        TreasuryChanged::dispatch($team->refresh());
    }
}

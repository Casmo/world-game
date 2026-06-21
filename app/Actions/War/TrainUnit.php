<?php

namespace App\Actions\War;

use App\Enums\UnitStatus;
use App\Enums\UnitType;
use App\Exceptions\InsufficientTreasuryException;
use App\Models\Building;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

/**
 * Train Units at a military Building: the treasury pays the training cost up
 * front and the Units enter Training, becoming Idle once their timer elapses
 * (resolved by the sweep, ADR-0007).
 */
class TrainUnit
{
    /**
     * @throws InsufficientTreasuryException
     */
    public function handle(Team $team, Building $barracks, UnitType $type, int $quantity): void
    {
        DB::transaction(function () use ($team, $barracks, $type, $quantity) {
            $cost = $type->trainingCost() * $quantity;

            if (! $team->withdrawTreasury($cost)) {
                throw new InsufficientTreasuryException;
            }

            $availableAt = now()->addSeconds($type->trainingSeconds());

            for ($i = 0; $i < $quantity; $i++) {
                $team->units()->create([
                    'type' => $type,
                    'status' => UnitStatus::Training,
                    'tile_id' => $barracks->tile_id,
                    'available_at' => $availableAt,
                ]);
            }

            $team->startMaintenanceClock();
        });
    }
}

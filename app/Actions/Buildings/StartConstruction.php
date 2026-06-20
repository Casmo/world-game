<?php

namespace App\Actions\Buildings;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Exceptions\InsufficientEnergyException;
use App\Exceptions\PlayerBusyException;
use App\Exceptions\WorkSlotsFullException;
use App\Models\Activity;
use App\Models\Building;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * A player joins the construction of a Building: a Construct Activity (a timed
 * shift, ADR-0007) that, on completion, adds work toward finishing it. More
 * helpers (up to the Building's work-slots) finish it faster; the shift duration
 * is the minimum time floor.
 */
class StartConstruction
{
    /**
     * @throws PlayerBusyException|WorkSlotsFullException|InsufficientEnergyException
     */
    public function handle(User $user, Building $building): Activity
    {
        return DB::transaction(function () use ($user, $building) {
            // Lock the Building so concurrent joiners can't exceed work-slots (ADR-0010).
            $building = Building::query()->whereKey($building->getKey())->lockForUpdate()->firstOrFail();

            if ($user->activeActivity() !== null) {
                throw new PlayerBusyException;
            }

            if (! $building->hasFreeWorkSlot()) {
                throw new WorkSlotsFullException;
            }

            if ($user->currentEnergy() < Building::CONSTRUCT_ENERGY_COST) {
                throw new InsufficientEnergyException;
            }

            $user->forceFill([
                'energy' => $user->energy - Building::CONSTRUCT_ENERGY_COST,
            ])->save();

            return $user->activities()->create([
                'type' => ActivityType::Construct,
                'status' => ActivityStatus::Active,
                'target_type' => $building->getMorphClass(),
                'target_id' => $building->getKey(),
                'started_at' => now(),
                'completes_at' => now()->addSeconds(ActivityType::Construct->durationSeconds()),
            ]);
        });
    }
}

<?php

namespace App\Console\Commands;

use App\Enums\UnitStatus;
use App\Models\Activity;
use App\Models\Team;
use App\Models\Unit;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('world:sweep')]
#[Description('Advance the world: apply every due Activity completion and military upkeep.')]
class RunWorldSweep extends Command
{
    /**
     * Advance the world to now.
     *
     * The database is the source of truth (ADR-0007): each effect is applied
     * idempotently inside a locking transaction (ADR-0010), so re-running the
     * sweep never double-applies, and a long gap simply catches everything up.
     */
    public function handle(): int
    {
        $completions = $this->completeDueActivities();
        $activated = $this->activateTrainedUnits();
        $this->chargeDueMaintenance();

        $this->info("Swept {$completions} completion(s), activated {$activated} unit(s).");

        return self::SUCCESS;
    }

    /**
     * Apply every Activity whose completion time has passed.
     */
    private function completeDueActivities(): int
    {
        $dueIds = Activity::due()->pluck('id');

        foreach ($dueIds as $id) {
            DB::transaction(function () use ($id) {
                $activity = Activity::query()
                    ->whereKey($id)
                    ->lockForUpdate()
                    ->first();

                if ($activity?->isActive() && $activity->completes_at <= now()) {
                    $activity->complete();
                }
            });
        }

        return $dueIds->count();
    }

    /**
     * Turn Units whose training has finished from Training into Idle.
     */
    private function activateTrainedUnits(): int
    {
        $dueIds = Unit::dueToActivate()->pluck('id');

        foreach ($dueIds as $id) {
            DB::transaction(function () use ($id) {
                $unit = Unit::query()->whereKey($id)->lockForUpdate()->first();

                if ($unit?->status === UnitStatus::Training && $unit->available_at <= now()) {
                    $unit->update(['status' => UnitStatus::Idle]);
                }
            });
        }

        return $dueIds->count();
    }

    /**
     * Charge Unit maintenance for every cycle that has elapsed, per Team. Each
     * cycle is charged in its own locking transaction; offline gaps catch up by
     * advancing the due time one cycle at a time (ADR-0005/0007).
     */
    private function chargeDueMaintenance(): void
    {
        $cycle = (int) config('war.maintenance_cycle_seconds');

        $dueTeamIds = Team::query()
            ->whereNotNull('maintenance_due_at')
            ->where('maintenance_due_at', '<=', now())
            ->pluck('id');

        foreach ($dueTeamIds as $id) {
            DB::transaction(function () use ($id, $cycle) {
                $team = Team::query()->whereKey($id)->lockForUpdate()->first();

                // Safe to debit directly: the Team row is locked for the whole
                // catch-up and the charge is clamped to the treasury (ADR-0010).
                while ($team !== null && $team->maintenance_due_at !== null && $team->maintenance_due_at <= now()) {
                    $payable = min($team->maintenanceCostPerCycle(), $team->treasury);

                    $team->forceFill([
                        'treasury' => $team->treasury - $payable,
                        'maintenance_due_at' => $team->maintenance_due_at->copy()->addSeconds($cycle),
                    ])->save();
                }
            });
        }
    }
}

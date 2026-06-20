<?php

namespace App\Console\Commands;

use App\Models\Activity;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('world:sweep')]
#[Description('Advance the world: apply every due Activity completion.')]
class RunWorldSweep extends Command
{
    /**
     * Process all activities whose completion time has passed.
     *
     * The database is the source of truth (ADR-0007): each completion is applied
     * idempotently inside a locking transaction (ADR-0010), so re-running the
     * sweep never double-applies, and a long gap simply catches everything up.
     */
    public function handle(): int
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

        $this->info("Swept {$dueIds->count()} completion(s).");

        return self::SUCCESS;
    }
}

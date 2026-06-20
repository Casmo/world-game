<?php

namespace App\Models;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Events\ActivityCompleted;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'status' => ActivityStatus::class,
            'started_at' => 'datetime',
            'completes_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Activities that are due to complete (still active, past their completion time).
     *
     * @param  Builder<Activity>  $query
     */
    public function scopeDue(Builder $query, ?Carbon $now = null): void
    {
        $query->where('status', ActivityStatus::Active)
            ->where('completes_at', '<=', $now ?? now());
    }

    public function isActive(): bool
    {
        return $this->status === ActivityStatus::Active;
    }

    /**
     * Apply this activity's completion effect and mark it completed.
     *
     * Idempotent: a non-active activity is left untouched, so a re-run of the
     * sweep never double-applies an effect.
     */
    public function complete(): void
    {
        if (! $this->isActive()) {
            return;
        }

        match ($this->type) {
            ActivityType::Sleep => $this->user->restoreEnergyToFull(),
            ActivityType::Construct => $this->target?->addConstructionWork(Building::WORK_PER_SHIFT),
        };

        $this->forceFill([
            'status' => ActivityStatus::Completed,
            'completed_at' => now(),
        ])->save();

        ActivityCompleted::dispatch($this);
    }

    public function cancel(): void
    {
        if (! $this->isActive()) {
            return;
        }

        $this->update(['status' => ActivityStatus::Cancelled]);
    }
}

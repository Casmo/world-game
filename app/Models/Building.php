<?php

namespace App\Models;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\BuildingState;
use App\Enums\BuildingType;
use App\Events\BuildingConstructed;
use App\Events\TreasuryChanged;
use App\Events\WagePaid;
use App\Support\MarketCatalogue;
use Database\Factories\BuildingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Building extends Model
{
    /** @use HasFactory<BuildingFactory> */
    use HasFactory;

    /** The Tile's interior is a fixed PLOT_GRID x PLOT_GRID sub-grid. */
    public const PLOT_GRID = 10;

    /** Construction work contributed by one completed Construct shift. */
    public const WORK_PER_SHIFT = 10;

    /** Energy a player spends to perform one Construct shift. */
    public const CONSTRUCT_ENERGY_COST = 10;

    /** Energy a player spends to perform one production Work shift. */
    public const WORK_ENERGY_COST = 10;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => BuildingType::class,
            'state' => BuildingState::class,
            'built_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Tile, $this>
     */
    public function tile(): BelongsTo
    {
        return $this->belongsTo(Tile::class, 'tile_id', 'h3_index');
    }

    /**
     * The currently active Construct activities working this Building.
     *
     * @return MorphMany<Activity, $this>
     */
    public function constructionActivities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'target')
            ->where('type', ActivityType::Construct)
            ->where('status', ActivityStatus::Active);
    }

    public function isUnderConstruction(): bool
    {
        return $this->state === BuildingState::UnderConstruction;
    }

    public function activeBuilderCount(): int
    {
        return $this->constructionActivities()->count();
    }

    public function hasFreeWorkSlot(): bool
    {
        return $this->activeBuilderCount() < $this->type->workSlots();
    }

    /**
     * The currently active Work activities producing at this Building.
     *
     * @return MorphMany<Activity, $this>
     */
    public function productionActivities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'target')
            ->where('type', ActivityType::Work)
            ->where('status', ActivityStatus::Active);
    }

    public function activeWorkerCount(): int
    {
        return $this->productionActivities()->count();
    }

    public function hasFreeProductionSlot(): bool
    {
        return $this->activeWorkerCount() < $this->type->workSlots();
    }

    public function isBuilt(): bool
    {
        return $this->state === BuildingState::Built;
    }

    public function isProduction(): bool
    {
        return $this->type->producesResource() !== null;
    }

    /**
     * Apply one completed Work shift: the produced Resources accrue to the
     * owning Team, the worker earns Experience in this trade, and the worker is
     * paid a Wage from the treasury — one consistent step (called inside the
     * sweep's locking transaction).
     */
    public function produceFor(User $worker): void
    {
        $team = $this->tile->team;
        if ($team === null) {
            return;
        }

        $resource = $this->type->producesResource();

        if ($resource !== null) {
            $team->addResource($resource, $this->type->outputPerShift());
            $producedValue = $this->type->outputPerShift() * (new MarketCatalogue)->floor($resource);
            $wage = (int) floor($team->clampedWageShare() * $producedValue);
        } else {
            // Service Buildings produce no sellable goods; they pay a flat floor wage.
            $wage = (int) config('money.floor_wage');
        }

        $worker->addExperience($this->type, $this->type->experiencePerShift());

        $this->payWage($team, $worker, $wage);
    }

    /**
     * Transfer a Wage from the treasury to the worker, never paying more than
     * the treasury holds — so Wages can never bankrupt the Team (ADR-0006) and
     * the atomic debit keeps the treasury non-negative (ADR-0010).
     */
    private function payWage(Team $team, User $worker, int $wage): void
    {
        $treasury = (int) $team->newQuery()->whereKey($team->getKey())->value('treasury');
        $payable = min($wage, $treasury);

        if ($payable <= 0 || ! $team->withdrawTreasury($payable)) {
            return;
        }

        $worker->addBalance($payable);

        WagePaid::dispatch($worker->refresh(), $payable);
        TreasuryChanged::dispatch($team->refresh());
    }

    /**
     * Apply one Construct shift's worth of work; finish the Building once the
     * required work is met. Called from the sweep on shift completion.
     */
    public function addConstructionWork(int $amount): void
    {
        if (! $this->isUnderConstruction()) {
            return;
        }

        $this->work_done += $amount;

        if ($this->work_done >= $this->type->constructionWork()) {
            $this->work_done = $this->type->constructionWork();
            $this->state = BuildingState::Built;
            $this->built_at = now();
        }

        $this->save();

        if ($this->state === BuildingState::Built) {
            BuildingConstructed::dispatch($this);
        }
    }
}

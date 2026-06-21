<?php

namespace App\Models;

use App\Enums\UnitStatus;
use App\Enums\UnitType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A military Unit owned by a Team (ADR-0005).
 *
 * @property int $id
 * @property int $team_id
 * @property UnitType $type
 * @property UnitStatus $status
 * @property string|null $tile_id
 * @property Carbon|null $available_at
 */
class Unit extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => UnitType::class,
            'status' => UnitStatus::class,
            'available_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Tile, $this>
     */
    public function tile(): BelongsTo
    {
        return $this->belongsTo(Tile::class, 'tile_id', 'h3_index');
    }

    /**
     * Units whose training has finished and are due to become Idle.
     *
     * @param  Builder<Unit>  $query
     */
    public function scopeDueToActivate(Builder $query, ?Carbon $now = null): void
    {
        $query->where('status', UnitStatus::Training)
            ->where('available_at', '<=', $now ?? now());
    }
}

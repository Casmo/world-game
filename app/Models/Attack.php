<?php

namespace App\Models;

use App\Enums\AttackStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * An asynchronous attack marching from one Tile to another (ADR-0005).
 *
 * @property int $id
 * @property int $attacker_team_id
 * @property string $origin_tile_id
 * @property string $target_tile_id
 * @property AttackStatus $status
 * @property int $march_seconds
 * @property Carbon $arrives_at
 * @property array<string, mixed>|null $report
 */
class Attack extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => AttackStatus::class,
            'arrives_at' => 'datetime',
            'report' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function attacker(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'attacker_team_id');
    }

    /**
     * @return BelongsTo<Tile, $this>
     */
    public function targetTile(): BelongsTo
    {
        return $this->belongsTo(Tile::class, 'target_tile_id', 'h3_index');
    }

    /**
     * @return HasMany<Unit, $this>
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    /**
     * Attacks at a given status whose arrival time has passed.
     *
     * @param  Builder<Attack>  $query
     */
    public function scopeArrived(Builder $query, AttackStatus $status, ?Carbon $now = null): void
    {
        $query->where('status', $status)->where('arrives_at', '<=', $now ?? now());
    }
}

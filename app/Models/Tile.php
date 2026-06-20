<?php

namespace App\Models;

use App\Enums\TileResolutionStatus;
use App\Support\H3;
use Database\Factories\TileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tile extends Model
{
    /** @use HasFactory<TileFactory> */
    use HasFactory;

    protected $primaryKey = 'h3_index';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'base_resources' => 'array',
            'resolution_status' => TileResolutionStatus::class,
        ];
    }

    /**
     * The Team that owns this Tile, if any.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return HasMany<Building, $this>
     */
    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class, 'tile_id', 'h3_index');
    }

    /**
     * @param  Builder<Tile>  $query
     */
    public function scopeUnowned(Builder $query): void
    {
        $query->whereNull('team_id');
    }

    public function isOwned(): bool
    {
        return $this->team_id !== null;
    }

    public function isResolved(): bool
    {
        return $this->resolution_status === TileResolutionStatus::Resolved;
    }

    /**
     * The Tile's center coordinates, derived from its H3 index.
     *
     * @return array{0: float, 1: float} [lat, lng]
     */
    public function center(): array
    {
        return app(H3::class)->cellToLatLng($this->h3_index);
    }
}

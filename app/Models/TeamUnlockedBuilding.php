<?php

namespace App\Models;

use App\Enums\BuildingType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A Building type a Team has unlocked via the tech tree.
 *
 * @property int $id
 * @property int $team_id
 * @property BuildingType $building_type
 */
class TeamUnlockedBuilding extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'building_type' => BuildingType::class,
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}

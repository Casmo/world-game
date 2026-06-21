<?php

namespace App\Models;

use App\Enums\BuildingType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A Team's banked Research progress toward one Building type (ADR-0003).
 *
 * @property int $id
 * @property int $team_id
 * @property BuildingType $building_type
 * @property int $points
 */
class TeamResearchProgress extends Model
{
    protected $table = 'team_research_progress';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'building_type' => BuildingType::class,
            'points' => 'integer',
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

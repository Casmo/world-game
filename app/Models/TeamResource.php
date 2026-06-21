<?php

namespace App\Models;

use App\Enums\ResourceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A Team's running total of one Resource type.
 *
 * @property int $id
 * @property int $team_id
 * @property ResourceType $type
 * @property int $amount
 */
class TeamResource extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => ResourceType::class,
            'amount' => 'integer',
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

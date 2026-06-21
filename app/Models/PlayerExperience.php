<?php

namespace App\Models;

use App\Enums\BuildingType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A player's accumulated Experience in one trade (Building type).
 *
 * @property int $id
 * @property int $user_id
 * @property BuildingType $building_type
 * @property int $points
 */
class PlayerExperience extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'building_type' => BuildingType::class,
            'points' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

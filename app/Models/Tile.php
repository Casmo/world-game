<?php

namespace App\Models;

use App\Enums\TileResolutionStatus;
use App\Support\H3;
use Database\Factories\TileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

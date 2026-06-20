<?php

namespace App\Enums;

enum TileResolutionStatus: string
{
    /** The Tile exists but its Biome has not been fetched yet. */
    case Pending = 'pending';

    /** The Tile's Biome has been resolved and cached. */
    case Resolved = 'resolved';
}

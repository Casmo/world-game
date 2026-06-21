<?php

namespace App\Enums;

/**
 * The basic Resources a Tile yields through worked production Buildings. Every
 * Tile can produce these; rare resources (driving Trade and War) arrive later.
 */
enum ResourceType: string
{
    case Food = 'food';
    case Wood = 'wood';
    case Stone = 'stone';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}

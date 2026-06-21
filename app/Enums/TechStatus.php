<?php

namespace App\Enums;

/**
 * A Building type's tech-tree status from one Team's perspective (Fog of war).
 */
enum TechStatus: string
{
    /** Already unlocked — placeable now. */
    case Unlocked = 'unlocked';

    /** Not unlocked, but all prerequisites are — researchable now. */
    case Available = 'available';

    /** Not unlocked and at least one prerequisite is still missing. */
    case Locked = 'locked';
}

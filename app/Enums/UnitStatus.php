<?php

namespace App\Enums;

/**
 * The lifecycle state of a military Unit.
 */
enum UnitStatus: string
{
    /** Being trained; not yet usable (the sweep activates it at available_at). */
    case Training = 'training';

    /** Trained and at home, not assigned. */
    case Idle = 'idle';

    /** Standing on a Tile as a defender. */
    case Garrisoned = 'garrisoned';

    /** Marching to or from a target (in an attack). */
    case InTransit = 'in_transit';
}

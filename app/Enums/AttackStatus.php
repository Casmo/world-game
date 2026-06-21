<?php

namespace App\Enums;

/**
 * The lifecycle of an asynchronous attack (ADR-0005).
 */
enum AttackStatus: string
{
    /** Marching toward the target; the sweep resolves the battle on arrival. */
    case Marching = 'marching';

    /** Battle resolved; survivors marching home. */
    case Returning = 'returning';

    /** Fully concluded (survivors home, or the force was lost). */
    case Resolved = 'resolved';
}

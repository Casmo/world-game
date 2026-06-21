<?php

namespace App\Support;

/**
 * The computed result of one battle (ADR-0005). Pure data — the orchestration
 * layer applies the consequences (loot, losses) to Teams.
 */
class CombatOutcome
{
    public function __construct(
        public readonly float $effectiveAttack,
        public readonly float $effectiveDefense,
        public readonly bool $attackerWins,
        public readonly float $lootFraction,
    ) {}
}

<?php

namespace App\Support;

use App\Enums\UnitType;

/**
 * The combat-resolution engine (ADR-0005). Pure: given an attacker composition
 * and a defender garrison (counts keyed by UnitType value), it computes the
 * battle outcome — effective attack vs effective defence with type counters and
 * the defender advantage — and, at the raiding tier, the fraction of the
 * defender's Resources looted (scaled by margin, capped below razing).
 */
class CombatResolver
{
    /**
     * @param  array<string, int>  $attackers  unit-type value => count
     * @param  array<string, int>  $defenders  unit-type value => count
     */
    public function resolve(array $attackers, array $defenders): CombatOutcome
    {
        $defenderAdvantage = (float) config('war.defender_advantage');
        $counterBonus = (float) config('war.counter_bonus');

        $defenderCount = array_sum($defenders);

        $effectiveDefense = $this->rawStrength($defenders) * $defenderAdvantage;

        $effectiveAttack = 0.0;
        foreach ($attackers as $typeValue => $count) {
            $type = UnitType::from($typeValue);
            $counteredShare = $defenderCount > 0
                ? ($defenders[$type->counters()->value] ?? 0) / $defenderCount
                : 0.0;
            $multiplier = 1.0 + ($counterBonus - 1.0) * $counteredShare;
            $effectiveAttack += $count * $type->strength() * $multiplier;
        }

        $attackerWins = $effectiveAttack > $effectiveDefense;

        return new CombatOutcome(
            effectiveAttack: $effectiveAttack,
            effectiveDefense: $effectiveDefense,
            attackerWins: $attackerWins,
            lootFraction: $this->lootFraction($attackerWins, $effectiveAttack, $effectiveDefense),
        );
    }

    /**
     * @param  array<string, int>  $force
     */
    private function rawStrength(array $force): float
    {
        $total = 0.0;
        foreach ($force as $typeValue => $count) {
            $total += $count * UnitType::from($typeValue)->strength();
        }

        return $total;
    }

    /**
     * The fraction of the defender's Resources a successful raid loots, scaled
     * by how far the attack exceeded the defence, capped below the razing tier.
     */
    private function lootFraction(bool $attackerWins, float $effectiveAttack, float $effectiveDefense): float
    {
        if (! $attackerWins) {
            return 0.0;
        }

        $perMargin = (float) config('war.raid_loot_per_margin');
        $cap = (float) config('war.raid_loot_cap_fraction');

        // An undefended Tile is raided at the cap; otherwise scale by the margin.
        $margin = $effectiveDefense > 0 ? $effectiveAttack / $effectiveDefense : INF;

        return min($cap, ($margin - 1.0) * $perMargin);
    }
}

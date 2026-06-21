<?php

use App\Enums\UnitType;
use App\Support\CombatResolver;

beforeEach(function () {
    config([
        'war.defender_advantage' => 1.5,
        'war.counter_bonus' => 1.5,
        'war.raid_loot_per_margin' => 0.25,
        'war.raid_loot_cap_fraction' => 0.5,
    ]);
});

test('equal forces resolve in the defender’s favour', function () {
    $outcome = (new CombatResolver)->resolve(
        [UnitType::Infantry->value => 10],
        [UnitType::Infantry->value => 10],
    );

    expect($outcome->attackerWins)->toBeFalse();
});

test('a countering Unit type fights harder than a non-countering one', function () {
    $resolver = new CombatResolver;
    // Garrison is Infantry; Armor counters Infantry, Air does not. Both raw 100.
    $vsCounter = $resolver->resolve([UnitType::Armor->value => 4], [UnitType::Infantry->value => 10]);
    $vsNeutral = $resolver->resolve([UnitType::Air->value => 5], [UnitType::Infantry->value => 10]);

    expect($vsCounter->effectiveAttack)->toBeGreaterThan($vsNeutral->effectiveAttack);
});

test('a sufficiently larger force overcomes the defender advantage', function () {
    $outcome = (new CombatResolver)->resolve(
        [UnitType::Infantry->value => 30],
        [UnitType::Infantry->value => 10],
    );

    expect($outcome->attackerWins)->toBeTrue();
});

test('raid loot scales with margin and is capped, and is zero on defeat', function () {
    $resolver = new CombatResolver;

    // 30 vs 10 Infantry: effAtk 300 / effDef 150 = margin 2 -> (2-1)*0.25 = 0.25.
    expect($resolver->resolve([UnitType::Infantry->value => 30], [UnitType::Infantry->value => 10])->lootFraction)->toBe(0.25)
        // Overwhelming margin caps at 0.5.
        ->and($resolver->resolve([UnitType::Infantry->value => 1000], [UnitType::Infantry->value => 10])->lootFraction)->toBe(0.5)
        // Defender wins -> no loot.
        ->and($resolver->resolve([UnitType::Infantry->value => 5], [UnitType::Infantry->value => 10])->lootFraction)->toBe(0.0);
});

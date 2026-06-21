<?php

use App\Actions\War\LaunchAttack;
use App\Enums\AttackStatus;
use App\Enums\ResourceType;
use App\Enums\UnitStatus;
use App\Enums\UnitType;
use App\Events\BattleResolved;
use App\Events\IncomingAttack;
use App\Models\Team;
use App\Models\Tile;
use App\Support\H3;
use Illuminate\Support\Facades\Event;

function configureCombat(): void
{
    config([
        'war.defender_advantage' => 1.5,
        'war.counter_bonus' => 1.5,
        'war.raid_loot_per_margin' => 0.25,
        'war.raid_loot_cap_fraction' => 0.5,
        'war.march_seconds_per_ring' => 600,
        'war.attack_failure_penalty' => 50,
        'money.seed_capital' => 1000,
    ]);
}

function garrison(Team $team, Tile $tile, UnitType $type, int $count): void
{
    for ($i = 0; $i < $count; $i++) {
        $team->units()->create(['type' => $type, 'status' => UnitStatus::Garrisoned, 'tile_id' => $tile->h3_index]);
    }
}

test('launching an attack sends the chosen Units in transit toward the target', function () {
    [, $attacker, $attackerTile] = foundedTeam();
    [, , $targetTile] = foundedTeam();
    garrison($attacker, $attackerTile, UnitType::Infantry, 5);

    $attack = app(LaunchAttack::class)->handle($attacker, $targetTile, [UnitType::Infantry->value => 3]);

    expect($attacker->units()->where('status', UnitStatus::InTransit)->count())->toBe(3)
        ->and($attack->status)->toBe(AttackStatus::Marching);
});

test('a successful raid transfers Resources from defender to attacker on arrival', function () {
    configureCombat();
    [, $attacker, $attackerTile] = foundedTeam();
    [, $defender, $targetTile] = foundedTeam();
    garrison($attacker, $attackerTile, UnitType::Infantry, 30);
    $defender->addResource(ResourceType::Wood, 100);

    $attack = app(LaunchAttack::class)->handle($attacker, $targetTile, [UnitType::Infantry->value => 30]);

    $this->travel($attack->march_seconds + 60)->seconds();
    $this->artisan('world:sweep')->assertSuccessful();

    // Undefended Tile → loot capped at 0.5 → 50 Wood transferred.
    expect($defender->fresh()->resourceTotal(ResourceType::Wood))->toBe(50)
        ->and($attacker->fresh()->resourceTotal(ResourceType::Wood))->toBe(50)
        ->and($attack->fresh()->status)->toBe(AttackStatus::Returning);
});

test('a failed attack loses the attacking force and forfeits Money to the defender', function () {
    configureCombat();
    [, $attacker, $attackerTile] = foundedTeam();
    [, $defender, $targetTile] = foundedTeam();
    garrison($attacker, $attackerTile, UnitType::Infantry, 3);
    garrison($defender, $targetTile, UnitType::Infantry, 20);
    $defenderStart = $defender->fresh()->treasury;

    $attack = app(LaunchAttack::class)->handle($attacker, $targetTile, [UnitType::Infantry->value => 3]);
    $this->travel($attack->march_seconds + 60)->seconds();
    $this->artisan('world:sweep')->assertSuccessful();

    expect($attacker->fresh()->units()->count())->toBe(0)
        ->and($attack->fresh()->status)->toBe(AttackStatus::Resolved)
        ->and($attacker->fresh()->treasury)->toBe(1000 - 50)
        ->and($defender->fresh()->treasury)->toBe($defenderStart + 50);
});

test('surviving attackers return home and become idle', function () {
    configureCombat();
    [, $attacker, $attackerTile] = foundedTeam();
    [, $defender, $targetTile] = foundedTeam();
    garrison($attacker, $attackerTile, UnitType::Infantry, 30);
    $defender->addResource(ResourceType::Wood, 100);

    $attack = app(LaunchAttack::class)->handle($attacker, $targetTile, [UnitType::Infantry->value => 30]);
    $this->travel($attack->march_seconds + 60)->seconds();
    $this->artisan('world:sweep')->assertSuccessful();
    $this->travel($attack->march_seconds + 60)->seconds();
    $this->artisan('world:sweep')->assertSuccessful();

    expect($attacker->fresh()->units()->where('status', UnitStatus::Idle)->count())->toBe(30)
        ->and($attack->fresh()->status)->toBe(AttackStatus::Resolved)
        ->and($attacker->units()->first()->tile_id)->toBe($attackerTile->h3_index);
});

test('march time is derived from the H3 distance between the Tiles', function () {
    configureCombat();
    [, $attacker, $attackerTile] = foundedTeam();
    [, , $targetTile] = foundedTeam();
    garrison($attacker, $attackerTile, UnitType::Infantry, 1);

    $attack = app(LaunchAttack::class)->handle($attacker, $targetTile, [UnitType::Infantry->value => 1]);

    $distance = max(1, app(H3::class)->gridDistance($attackerTile->h3_index, $targetTile->h3_index));
    expect($attack->march_seconds)->toBe($distance * 600);
});

test('the defender is alerted on launch and both Teams on resolution', function () {
    Event::fake([IncomingAttack::class, BattleResolved::class]);
    configureCombat();
    [, $attacker, $attackerTile] = foundedTeam();
    [, $defender, $targetTile] = foundedTeam();
    garrison($attacker, $attackerTile, UnitType::Infantry, 30);

    $attack = app(LaunchAttack::class)->handle($attacker, $targetTile, [UnitType::Infantry->value => 30]);
    Event::assertDispatched(IncomingAttack::class, fn (IncomingAttack $e) => $e->defenderTeamId === $defender->id);

    $this->travel($attack->march_seconds + 60)->seconds();
    $this->artisan('world:sweep')->assertSuccessful();
    Event::assertDispatched(BattleResolved::class, fn (BattleResolved $e) => $e->defenderTeamId === $defender->id);
});

test('a Mayor/Officer can launch an attack via the endpoint', function () {
    configureCombat();
    [$owner, $attacker, $attackerTile] = foundedTeam();
    [, , $targetTile] = foundedTeam();
    garrison($attacker, $attackerTile, UnitType::Infantry, 5);

    $this->actingAs($owner)
        ->post('/attacks', ['target' => $targetTile->h3_index, 'units' => ['infantry' => 3]])
        ->assertRedirect();

    expect($attacker->units()->where('status', UnitStatus::InTransit)->count())->toBe(3);
});

test('a Member cannot launch an attack', function () {
    configureCombat();
    [, $attacker, $attackerTile] = foundedTeam();
    [, , $targetTile] = foundedTeam();
    garrison($attacker, $attackerTile, UnitType::Infantry, 5);
    $member = teamMember($attacker);
    $member->switchTeam($attacker);

    $this->actingAs($member->fresh())
        ->post('/attacks', ['target' => $targetTile->h3_index, 'units' => ['infantry' => 3]])
        ->assertForbidden();

    expect($attacker->units()->where('status', UnitStatus::InTransit)->count())->toBe(0);
});

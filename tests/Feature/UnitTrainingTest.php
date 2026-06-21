<?php

use App\Actions\War\DisbandUnits;
use App\Actions\War\GarrisonUnit;
use App\Actions\War\TrainUnit;
use App\Enums\BuildingType;
use App\Enums\UnitStatus;
use App\Enums\UnitType;
use App\Exceptions\InsufficientTreasuryException;
use App\Models\Building;
use App\Models\Team;
use App\Models\Tile;
use App\Models\Unit;
use Inertia\Testing\AssertableInertia as Assert;

function idleUnit(Team $team, Tile $tile, UnitType $type = UnitType::Infantry): Unit
{
    return $team->units()->create(['type' => $type, 'status' => UnitStatus::Idle, 'tile_id' => $tile->h3_index]);
}

function builtBarracks(Tile $tile): Building
{
    return Building::factory()->for($tile, 'tile')->ofType(BuildingType::Barracks)->built()->create(['plot_x' => 0, 'plot_y' => 0]);
}

test('training Units creates them in Training status for the Team', function () {
    config(['money.seed_capital' => 1000]);
    [, $team, $tile] = foundedTeam();
    $barracks = builtBarracks($tile);

    app(TrainUnit::class)->handle($team, $barracks, UnitType::Infantry, 3);

    expect($team->units()->where('status', UnitStatus::Training)->count())->toBe(3);
});

test('training charges the treasury the Units training cost', function () {
    config(['money.seed_capital' => 1000]);
    [, $team, $tile] = foundedTeam();
    $barracks = builtBarracks($tile);

    app(TrainUnit::class)->handle($team, $barracks, UnitType::Infantry, 3);

    expect($team->fresh()->treasury)->toBe(1000 - 3 * UnitType::Infantry->trainingCost());
});

test('training is rejected and trains nothing when the treasury cannot afford it', function () {
    config(['money.seed_capital' => 10]);
    [, $team, $tile] = foundedTeam();
    $barracks = builtBarracks($tile);

    expect(fn () => app(TrainUnit::class)->handle($team, $barracks, UnitType::Armor, 1))
        ->toThrow(InsufficientTreasuryException::class);

    expect($team->fresh()->treasury)->toBe(10)
        ->and($team->units()->count())->toBe(0);
});

test('the sweep turns a Unit Idle once its training time elapses', function () {
    config(['money.seed_capital' => 1000]);
    [, $team, $tile] = foundedTeam();
    $barracks = builtBarracks($tile);
    app(TrainUnit::class)->handle($team, $barracks, UnitType::Infantry, 2);

    $this->travel(UnitType::Infantry->trainingSeconds() + 60)->seconds();
    $this->artisan('world:sweep')->assertSuccessful();

    expect($team->units()->where('status', UnitStatus::Idle)->count())->toBe(2)
        ->and($team->units()->where('status', UnitStatus::Training)->count())->toBe(0);
});

test('the sweep charges Unit maintenance from the treasury each cycle', function () {
    config(['money.seed_capital' => 1000, 'war.maintenance_cycle_seconds' => 3600]);
    [, $team, $tile] = foundedTeam();
    $barracks = builtBarracks($tile);
    app(TrainUnit::class)->handle($team, $barracks, UnitType::Infantry, 2);

    // Finish training (units become Idle), no maintenance due yet.
    $this->travel(UnitType::Infantry->trainingSeconds() + 60)->seconds();
    $this->artisan('world:sweep')->assertSuccessful();
    $afterTraining = $team->fresh()->treasury;

    // One maintenance cycle elapses.
    $this->travel(3600)->seconds();
    $this->artisan('world:sweep')->assertSuccessful();

    expect($team->fresh()->treasury)->toBe($afterTraining - 2 * UnitType::Infantry->maintenancePerCycle());
});

test('maintenance cycles elapsed while offline all reconcile on the next sweep', function () {
    config(['money.seed_capital' => 1000, 'war.maintenance_cycle_seconds' => 3600]);
    [, $team, $tile] = foundedTeam();
    $barracks = builtBarracks($tile);
    app(TrainUnit::class)->handle($team, $barracks, UnitType::Infantry, 1);

    $this->travel(UnitType::Infantry->trainingSeconds() + 60)->seconds();
    $this->artisan('world:sweep')->assertSuccessful();
    $afterTraining = $team->fresh()->treasury;

    // Three cycles pass before the next sweep runs.
    $this->travel(3 * 3600 + 60)->seconds();
    $this->artisan('world:sweep')->assertSuccessful();

    expect($team->fresh()->treasury)->toBe($afterTraining - 3 * UnitType::Infantry->maintenancePerCycle());
});

test('disbanding removes the Units from the Team', function () {
    [, $team, $tile] = foundedTeam();
    $a = idleUnit($team, $tile);
    $b = idleUnit($team, $tile);

    app(DisbandUnits::class)->handle($team, [$a->id, $b->id]);

    expect($team->units()->count())->toBe(0);
});

test('an idle Unit can be garrisoned on a Tile', function () {
    [, $team, $tile] = foundedTeam();
    $unit = idleUnit($team, $tile);

    app(GarrisonUnit::class)->handle($unit, $tile);

    expect($unit->fresh()->status)->toBe(UnitStatus::Garrisoned)
        ->and($unit->fresh()->tile_id)->toBe($tile->h3_index);
});

test('a Mayor/Officer can train Units via the endpoint', function () {
    config(['money.seed_capital' => 1000]);
    [$owner, $team, $tile] = foundedTeam();
    $barracks = builtBarracks($tile);

    $this->actingAs($owner)
        ->post('/units', ['building' => $barracks->id, 'type' => 'infantry', 'quantity' => 2])
        ->assertRedirect();

    expect($team->units()->count())->toBe(2);
});

test('a Member cannot train Units', function () {
    config(['money.seed_capital' => 1000]);
    [, $team, $tile] = foundedTeam();
    $barracks = builtBarracks($tile);
    $member = teamMember($team);
    $member->switchTeam($team);

    $this->actingAs($member->fresh())
        ->post('/units', ['building' => $barracks->id, 'type' => 'infantry', 'quantity' => 1])
        ->assertForbidden();

    expect($team->units()->count())->toBe(0);
});

test('the forces view shows the Team’s own Units and never another Team’s', function () {
    $this->withoutVite();
    [$owner, $team, $tile] = foundedTeam();
    idleUnit($team, $tile);
    idleUnit($team, $tile, UnitType::Armor);

    [, $other, $otherTile] = foundedTeam();
    idleUnit($other, $otherTile);

    $this->actingAs($owner)
        ->get('/units')
        ->assertInertia(fn (Assert $page) => $page
            ->component('units')
            ->has('units', 2)
        );
});

test('a Mayor/Officer can disband Units via the endpoint', function () {
    [$owner, $team, $tile] = foundedTeam();
    $unit = idleUnit($team, $tile);

    $this->actingAs($owner)
        ->delete('/units', ['units' => [$unit->id]])
        ->assertRedirect();

    expect($team->units()->count())->toBe(0);
});

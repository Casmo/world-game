<?php

use App\Actions\Buildings\StartWork;
use App\Enums\ActivityType;
use App\Enums\BuildingType;
use App\Enums\ResourceType;
use App\Exceptions\InsufficientEnergyException;
use App\Exceptions\PlayerBusyException;
use App\Exceptions\WorkSlotsFullException;
use App\Models\Building;
use App\Models\Tile;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function builtProductionBuilding(Tile $tile, BuildingType $type = BuildingType::Farm): Building
{
    return Building::factory()->for($tile, 'tile')->ofType($type)->built()->create(['plot_x' => 0, 'plot_y' => 0]);
}

test('a member can start a Work shift on a built production Building, spending Energy', function () {
    [, $team, $tile] = foundedTeam();
    $building = builtProductionBuilding($tile, BuildingType::Farm);
    $worker = teamMember($team, energy: 100);

    app(StartWork::class)->handle($worker, $building);

    expect($worker->fresh()->energy)->toBe(100 - Building::WORK_ENERGY_COST)
        ->and($worker->activeActivity()?->type)->toBe(ActivityType::Work);
});

test('a completed Work shift produces Resources to the Team and Experience to the worker', function () {
    [, $team, $tile] = foundedTeam();
    $building = builtProductionBuilding($tile, BuildingType::Farm);
    $worker = teamMember($team);

    app(StartWork::class)->handle($worker, $building);

    $this->travel(2)->hours();
    $this->artisan('world:sweep')->assertSuccessful();

    expect($team->fresh()->resourceTotal(ResourceType::Food))->toBe(BuildingType::Farm->outputPerShift())
        ->and($worker->fresh()->experienceIn(BuildingType::Farm))->toBe(BuildingType::Farm->experiencePerShift());
});

test('a Building’s work-slot cap cannot be exceeded by workers', function () {
    [, $team, $tile] = foundedTeam();
    // LumberCamp has 2 work-slots.
    $building = builtProductionBuilding($tile, BuildingType::LumberCamp);

    app(StartWork::class)->handle(teamMember($team), $building);
    app(StartWork::class)->handle(teamMember($team), $building);

    expect(fn () => app(StartWork::class)->handle(teamMember($team), $building))
        ->toThrow(WorkSlotsFullException::class);
});

test('a busy player cannot start a second Work shift', function () {
    [, $team, $tile] = foundedTeam();
    $building = builtProductionBuilding($tile, BuildingType::Farm);
    $worker = teamMember($team);

    app(StartWork::class)->handle($worker, $building);

    expect(fn () => app(StartWork::class)->handle($worker, $building))
        ->toThrow(PlayerBusyException::class);
});

test('a player without enough Energy cannot Work', function () {
    [, $team, $tile] = foundedTeam();
    $building = builtProductionBuilding($tile, BuildingType::Farm);
    $broke = teamMember($team, energy: Building::WORK_ENERGY_COST - 1);

    expect(fn () => app(StartWork::class)->handle($broke, $building))
        ->toThrow(InsufficientEnergyException::class);
});

test('a member can start a Work shift via the endpoint', function () {
    [, $team, $tile] = foundedTeam();
    $building = builtProductionBuilding($tile, BuildingType::Farm);
    $member = teamMember($team);

    $this->actingAs($member)
        ->post("/buildings/{$building->id}/work")
        ->assertRedirect();

    expect($member->activeActivity()?->type)->toBe(ActivityType::Work);
});

test('an under-construction Building cannot be worked', function () {
    [, $team, $tile] = foundedTeam();
    $building = Building::factory()->for($tile, 'tile')->ofType(BuildingType::Farm)->create(['plot_x' => 0, 'plot_y' => 0]);
    $member = teamMember($team);

    $this->actingAs($member)
        ->post("/buildings/{$building->id}/work")
        ->assertStatus(422);

    expect($member->activeActivity())->toBeNull();
});

test('a non-member cannot work another Team’s Building', function () {
    [, , $tile] = foundedTeam();
    $building = builtProductionBuilding($tile, BuildingType::Farm);
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->post("/buildings/{$building->id}/work")
        ->assertForbidden();
});

test('the City page shows the Team’s Resource totals and the player’s Experience', function () {
    $this->withoutVite();
    [, $team, $tile] = foundedTeam();
    $building = builtProductionBuilding($tile, BuildingType::Farm);
    $worker = teamMember($team);

    app(StartWork::class)->handle($worker, $building);
    $this->travel(2)->hours();
    $this->artisan('world:sweep')->assertSuccessful();

    $this->actingAs($worker)
        ->get("/tiles/{$tile->h3_index}/city")
        ->assertInertia(fn (Assert $page) => $page
            ->where('resources.food', BuildingType::Farm->outputPerShift())
            ->where('experience.farm', BuildingType::Farm->experiencePerShift())
        );
});

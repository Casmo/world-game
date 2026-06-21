<?php

use App\Actions\Buildings\PlaceBuilding;
use App\Actions\Buildings\StartConstruction;
use App\Actions\Teams\CreateTeam;
use App\Enums\ActivityType;
use App\Enums\BuildingState;
use App\Enums\BuildingType;
use App\Enums\TeamRole;
use App\Events\BuildingConstructed;
use App\Exceptions\InsufficientEnergyException;
use App\Exceptions\WorkSlotsFullException;
use App\Models\Building;
use App\Models\Team;
use App\Models\Tile;
use App\Models\User;
use Illuminate\Support\Facades\Event;

/**
 * Found a Team (which claims a starting Tile) and return [owner, team, tile].
 *
 * @return array{0: User, 1: Team, 2: Tile}
 */
function foundedTeam(): array
{
    $owner = User::factory()->create();
    $team = app(CreateTeam::class)->handle($owner, 'Acme');

    return [$owner, $team, $team->tiles()->first()];
}

function teamMember(Team $team, int $energy = 100): User
{
    $user = User::factory()->create(['energy' => $energy]);
    $team->memberships()->create(['user_id' => $user->id, 'role' => TeamRole::Member]);

    return $user;
}

test('a Mayor/Officer can place a Building on an empty Plot', function () {
    [$owner, $team, $tile] = foundedTeam();

    $this->actingAs($owner)
        ->post('/buildings', ['tile' => $tile->h3_index, 'type' => 'farm', 'plot_x' => 0, 'plot_y' => 0])
        ->assertRedirect();

    $building = Building::firstWhere('tile_id', $tile->h3_index);
    expect($building->type)->toBe(BuildingType::Farm)
        ->and($building->state)->toBe(BuildingState::UnderConstruction)
        ->and($building->work_done)->toBe(0);
});

test('a Member cannot place a Building', function () {
    [, $team, $tile] = foundedTeam();
    $member = teamMember($team);

    $this->actingAs($member)
        ->post('/buildings', ['tile' => $tile->h3_index, 'type' => 'farm', 'plot_x' => 1, 'plot_y' => 1])
        ->assertForbidden();

    expect(Building::count())->toBe(0);
});

test('a Building cannot be placed on an occupied Plot', function () {
    [$owner, $team, $tile] = foundedTeam();
    app(PlaceBuilding::class)->handle($tile, BuildingType::Farm, 2, 2);

    $this->actingAs($owner)
        ->post('/buildings', ['tile' => $tile->h3_index, 'type' => 'quarry', 'plot_x' => 2, 'plot_y' => 2])
        ->assertStatus(422);

    expect(Building::where('tile_id', $tile->h3_index)->count())->toBe(1);
});

test('a member can start constructing a Building, spending Energy', function () {
    [, $team, $tile] = foundedTeam();
    $building = app(PlaceBuilding::class)->handle($tile, BuildingType::Farm, 0, 0);
    $member = teamMember($team, energy: 100);

    $this->actingAs($member)
        ->post("/buildings/{$building->id}/construct")
        ->assertRedirect();

    expect($member->fresh()->energy)->toBe(100 - Building::CONSTRUCT_ENERGY_COST)
        ->and($member->activeActivity()?->type)->toBe(ActivityType::Construct);
});

test('construction completes via the sweep and broadcasts to the Team', function () {
    Event::fake([BuildingConstructed::class]);
    [, $team, $tile] = foundedTeam();
    // LumberCamp needs 20 work; 2 work-slots; 10 work per shift → 2 builders, one shift.
    $building = app(PlaceBuilding::class)->handle($tile, BuildingType::LumberCamp, 0, 0);

    foreach ([teamMember($team), teamMember($team)] as $builder) {
        app(StartConstruction::class)->handle($builder, $building);
    }

    $this->travel(2)->hours();
    $this->artisan('world:sweep')->assertSuccessful();

    expect($building->fresh()->state)->toBe(BuildingState::Built)
        ->and($building->fresh()->built_at)->not->toBeNull();
    Event::assertDispatched(BuildingConstructed::class);
});

test('more helpers finish faster: one builder needs several shifts', function () {
    [, $team, $tile] = foundedTeam();
    // Farm needs 30 work → 3 shifts for a single builder.
    $building = app(PlaceBuilding::class)->handle($tile, BuildingType::Farm, 0, 0);
    app(StartConstruction::class)->handle(teamMember($team), $building);

    $this->travel(2)->hours();
    $this->artisan('world:sweep')->assertSuccessful();

    expect($building->fresh()->state)->toBe(BuildingState::UnderConstruction)
        ->and($building->fresh()->work_done)->toBe(Building::WORK_PER_SHIFT);
});

test('a Building’s work-slot cap cannot be exceeded', function () {
    [, $team, $tile] = foundedTeam();
    // LumberCamp has 2 work-slots.
    $building = app(PlaceBuilding::class)->handle($tile, BuildingType::LumberCamp, 0, 0);

    app(StartConstruction::class)->handle(teamMember($team), $building);
    app(StartConstruction::class)->handle(teamMember($team), $building);

    expect(fn () => app(StartConstruction::class)->handle(teamMember($team), $building))
        ->toThrow(WorkSlotsFullException::class);
});

test('a player without enough Energy cannot construct', function () {
    [, $team, $tile] = foundedTeam();
    $building = app(PlaceBuilding::class)->handle($tile, BuildingType::Farm, 0, 0);
    $broke = teamMember($team, energy: Building::CONSTRUCT_ENERGY_COST - 1);

    expect(fn () => app(StartConstruction::class)->handle($broke, $building))
        ->toThrow(InsufficientEnergyException::class);
});

test('an already-built Building cannot be constructed further', function () {
    [, $team, $tile] = foundedTeam();
    $building = Building::factory()->for($tile, 'tile')->ofType(BuildingType::Farm)->built()->create();
    $member = teamMember($team);

    $this->actingAs($member)
        ->post("/buildings/{$building->id}/construct")
        ->assertStatus(422);
});

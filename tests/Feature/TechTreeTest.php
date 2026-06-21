<?php

use App\Enums\BuildingType;
use App\Enums\TechStatus;
use App\Models\Building;
use App\Models\Team;
use App\Support\TechTree;
use Inertia\Testing\AssertableInertia as Assert;

test('a newly founded Team has the default Building types unlocked', function () {
    [, $team] = foundedTeam();

    expect($team->hasUnlocked(BuildingType::Farm))->toBeTrue()
        ->and($team->hasUnlocked(BuildingType::LumberCamp))->toBeTrue()
        ->and($team->hasUnlocked(BuildingType::Quarry))->toBeFalse();
});

test('a default-unlocked Building type can be placed', function () {
    [$owner, , $tile] = foundedTeam();

    $this->actingAs($owner)
        ->post('/buildings', ['tile' => $tile->h3_index, 'type' => 'farm', 'plot_x' => 0, 'plot_y' => 0])
        ->assertRedirect();

    expect(Building::where('tile_id', $tile->h3_index)->count())->toBe(1);
});

test('a not-yet-unlocked Building type cannot be placed', function () {
    [$owner, , $tile] = foundedTeam();

    $this->actingAs($owner)
        ->post('/buildings', ['tile' => $tile->h3_index, 'type' => 'quarry', 'plot_x' => 0, 'plot_y' => 0])
        ->assertStatus(422);

    expect(Building::where('tile_id', $tile->h3_index)->count())->toBe(0);
});

test('a Building is unlocked, available (prerequisites met), or locked per Team', function () {
    $team = Team::factory()->create();
    $team->unlockBuilding(BuildingType::Farm);

    $tree = app(TechTree::class);

    expect($tree->statusFor($team, BuildingType::Farm))->toBe(TechStatus::Unlocked)
        ->and($tree->statusFor($team, BuildingType::LumberCamp))->toBe(TechStatus::Available)
        ->and($tree->statusFor($team, BuildingType::Quarry))->toBe(TechStatus::Locked);
});

test('the tech tree view exposes each Building’s prerequisites, cost, and status', function () {
    $this->withoutVite();
    [$owner] = foundedTeam();

    $this->actingAs($owner)
        ->get('/tech-tree')
        ->assertInertia(fn (Assert $page) => $page
            ->component('tech-tree')
            ->has('buildings', 6)
            ->where('buildings.2.type', 'quarry')
            ->where('buildings.2.prerequisites', ['lumber_camp'])
            ->where('buildings.2.cost', BuildingType::Quarry->researchCost())
            ->where('buildings.2.status', 'available')
        );
});

test('a Team’s unlocked set is isolated from other Teams (Fog of war)', function () {
    $this->withoutVite();
    [$ownerA, $teamA] = foundedTeam();
    [$ownerB] = foundedTeam();

    // Team A unlocks Quarry; Team B does not.
    $teamA->unlockBuilding(BuildingType::Quarry);

    $this->actingAs($ownerA)
        ->get('/tech-tree')
        ->assertInertia(fn (Assert $page) => $page->where('buildings.2.status', 'unlocked'));

    $this->actingAs($ownerB)
        ->get('/tech-tree')
        ->assertInertia(fn (Assert $page) => $page->where('buildings.2.status', 'available'));
});

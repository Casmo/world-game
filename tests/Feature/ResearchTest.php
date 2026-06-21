<?php

use App\Actions\Buildings\PlaceBuilding;
use App\Actions\Buildings\StartWork;
use App\Enums\BuildingType;
use App\Events\ResearchProgressed;
use App\Events\ResearchUnlocked;
use App\Events\WagePaid;
use App\Models\Building;
use App\Models\Tile;
use Illuminate\Support\Facades\Event;

function builtResearchLab(Tile $tile): Building
{
    return Building::factory()->for($tile, 'tile')->ofType(BuildingType::ResearchLab)->built()->create(['plot_x' => 0, 'plot_y' => 0]);
}

test('working the research Building accrues progress toward the current target', function () {
    [, $team, $tile] = foundedTeam();
    $team->setResearchTarget(BuildingType::Quarry);
    $lab = builtResearchLab($tile);
    $worker = teamMember($team);

    app(StartWork::class)->handle($worker, $lab);
    $this->travel(2)->hours();
    $this->artisan('world:sweep')->assertSuccessful();

    expect($team->researchProgress(BuildingType::Quarry))->toBe(Building::RESEARCH_PER_SHIFT);
});

test('reaching the target cost unlocks the Building, clears the target, and makes it placeable', function () {
    [, $team, $tile] = foundedTeam();
    $team->setResearchTarget(BuildingType::Quarry);
    $team->addResearchProgress(BuildingType::Quarry, BuildingType::Quarry->researchCost() - Building::RESEARCH_PER_SHIFT);
    $lab = builtResearchLab($tile);
    $worker = teamMember($team);

    app(StartWork::class)->handle($worker, $lab);
    $this->travel(2)->hours();
    $this->artisan('world:sweep')->assertSuccessful();

    expect($team->fresh()->hasUnlocked(BuildingType::Quarry))->toBeTrue()
        ->and($team->fresh()->researchTarget())->toBeNull();

    // Now placeable per the tech-tree gating (#20) — no exception.
    $building = app(PlaceBuilding::class)->handle($tile->fresh(), BuildingType::Quarry, 5, 5);
    expect($building->type)->toBe(BuildingType::Quarry);
});

test('more research workers (up to work-slots) accrue progress faster', function () {
    [, $team, $tile] = foundedTeam();
    $team->setResearchTarget(BuildingType::Quarry);
    $lab = builtResearchLab($tile); // ResearchLab has 3 work-slots

    app(StartWork::class)->handle(teamMember($team), $lab);
    app(StartWork::class)->handle(teamMember($team), $lab);

    $this->travel(2)->hours();
    $this->artisan('world:sweep')->assertSuccessful();

    expect($team->researchProgress(BuildingType::Quarry))->toBe(2 * Building::RESEARCH_PER_SHIFT);
});

test('switching the research target preserves previously banked progress', function () {
    [, $team] = foundedTeam();
    $team->setResearchTarget(BuildingType::Quarry);
    $team->addResearchProgress(BuildingType::Quarry, 30);

    $team->setResearchTarget(BuildingType::Bar);
    $team->addResearchProgress(BuildingType::Bar, 10);
    $team->setResearchTarget(BuildingType::Quarry);

    expect($team->researchProgress(BuildingType::Quarry))->toBe(30)
        ->and($team->researchProgress(BuildingType::Bar))->toBe(10);
});

test('a research shift pays the flat floor wage and grants research Experience', function () {
    config(['money.seed_capital' => 1000, 'money.floor_wage' => 2]);
    [, $team, $tile] = foundedTeam();
    $team->setResearchTarget(BuildingType::Quarry);
    $lab = builtResearchLab($tile);
    $worker = teamMember($team);

    app(StartWork::class)->handle($worker, $lab);
    $this->travel(2)->hours();
    $this->artisan('world:sweep')->assertSuccessful();

    expect($worker->fresh()->balance)->toBe(2)
        ->and($worker->fresh()->experienceIn(BuildingType::ResearchLab))->toBe(BuildingType::ResearchLab->experiencePerShift());
});

test('research progress and unlock broadcast to the Team', function () {
    Event::fake([ResearchProgressed::class, ResearchUnlocked::class, WagePaid::class]);
    [, $team, $tile] = foundedTeam();
    $team->setResearchTarget(BuildingType::Quarry);
    $team->addResearchProgress(BuildingType::Quarry, BuildingType::Quarry->researchCost() - Building::RESEARCH_PER_SHIFT);
    $lab = builtResearchLab($tile);

    app(StartWork::class)->handle(teamMember($team), $lab);
    $this->travel(2)->hours();
    $this->artisan('world:sweep')->assertSuccessful();

    Event::assertDispatched(ResearchProgressed::class, fn (ResearchProgressed $e) => $e->team->is($team) && $e->target === BuildingType::Quarry);
    Event::assertDispatched(ResearchUnlocked::class, fn (ResearchUnlocked $e) => $e->team->is($team) && $e->unlocked === BuildingType::Quarry);
});

test('a Mayor/Officer can set the research target', function () {
    [$owner, $team] = foundedTeam();

    $this->actingAs($owner)
        ->post('/research/target', ['target' => 'quarry'])
        ->assertRedirect();

    expect($team->fresh()->researchTarget())->toBe(BuildingType::Quarry);
});

test('a Member cannot set the research target', function () {
    [, $team] = foundedTeam();
    $member = teamMember($team);
    $member->switchTeam($team);

    $this->actingAs($member->fresh())
        ->post('/research/target', ['target' => 'quarry'])
        ->assertForbidden();

    expect($team->fresh()->researchTarget())->toBeNull();
});

test('an already-unlocked Building cannot be set as the research target', function () {
    [$owner, $team] = foundedTeam();

    // Farm is default-unlocked.
    $this->actingAs($owner)
        ->post('/research/target', ['target' => 'farm'])
        ->assertStatus(422);

    expect($team->fresh()->researchTarget())->toBeNull();
});

test('a target with unmet prerequisites cannot be set', function () {
    [$owner, $team] = foundedTeam();
    // Quarry needs LumberCamp; drop it so Quarry is locked.
    $team->unlockedBuildings()->where('building_type', BuildingType::LumberCamp)->delete();

    $this->actingAs($owner)
        ->post('/research/target', ['target' => 'quarry'])
        ->assertStatus(422);

    expect($team->fresh()->researchTarget())->toBeNull();
});

<?php

use App\Actions\Buildings\StartWork;
use App\Enums\BuildingType;
use App\Enums\TeamRole;
use App\Events\TreasuryChanged;
use App\Events\WagePaid;
use App\Models\Building;
use App\Models\User;
use Illuminate\Support\Facades\Event;

test('a completed production shift pays the worker a share-of-value Wage from the treasury', function () {
    config(['money.seed_capital' => 1000, 'money.wage_share_floor' => 0.1, 'money.wage_share_cap' => 0.5]);
    config(['market.prices.wood' => ['floor' => 2, 'ceiling' => 5]]);
    [, $team, $tile] = foundedTeam();
    $team->forceFill(['wage_share' => 0.25])->save();
    $building = builtProductionBuilding($tile, BuildingType::LumberCamp);
    $worker = teamMember($team);

    app(StartWork::class)->handle($worker, $building);
    $this->travel(2)->hours();
    $this->artisan('world:sweep')->assertSuccessful();

    // LumberCamp: 8 output x floor 2 = 16 produced value; 0.25 share = 4.
    expect($worker->fresh()->balance)->toBe(4)
        ->and($team->fresh()->treasury)->toBe(1000 - 4);
});

test('a completed service-Building shift pays the flat floor wage', function () {
    config(['money.seed_capital' => 1000, 'money.floor_wage' => 2]);
    [, $team, $tile] = foundedTeam();
    $building = Building::factory()->for($tile, 'tile')->ofType(BuildingType::Bar)->built()->create(['plot_x' => 0, 'plot_y' => 0]);
    $worker = teamMember($team);

    app(StartWork::class)->handle($worker, $building);
    $this->travel(2)->hours();
    $this->artisan('world:sweep')->assertSuccessful();

    expect($worker->fresh()->balance)->toBe(2)
        ->and($team->fresh()->treasury)->toBe(1000 - 2);
});

test('paying a Wage broadcasts to the worker and the Team', function () {
    Event::fake([WagePaid::class, TreasuryChanged::class]);
    config(['money.seed_capital' => 1000]);
    config(['market.prices.wood' => ['floor' => 2, 'ceiling' => 5]]);
    [, $team, $tile] = foundedTeam();
    $building = builtProductionBuilding($tile, BuildingType::LumberCamp);
    $worker = teamMember($team);

    app(StartWork::class)->handle($worker, $building);
    $this->travel(2)->hours();
    $this->artisan('world:sweep')->assertSuccessful();

    Event::assertDispatched(WagePaid::class, fn (WagePaid $e) => $e->worker->is($worker) && $e->amount > 0);
    Event::assertDispatched(TreasuryChanged::class, fn (TreasuryChanged $e) => $e->team->is($team));
});

test('a generous wage share never pushes the treasury negative', function () {
    config(['money.seed_capital' => 1, 'money.wage_share_floor' => 0.1, 'money.wage_share_cap' => 0.5]);
    config(['market.prices.wood' => ['floor' => 2, 'ceiling' => 5]]);
    [, $team, $tile] = foundedTeam();
    $team->forceFill(['wage_share' => 0.5])->save();
    $building = builtProductionBuilding($tile, BuildingType::LumberCamp);
    $worker = teamMember($team);

    // Desired wage (0.5 x 16 = 8) far exceeds the tiny treasury.
    app(StartWork::class)->handle($worker, $building);
    $this->travel(2)->hours();
    $this->artisan('world:sweep')->assertSuccessful();

    expect($team->fresh()->treasury)->toBe(0)
        ->and($worker->fresh()->balance)->toBe(1);
});

test('Wages from a shift completed while offline are applied on the next sweep', function () {
    config(['money.seed_capital' => 1000, 'money.wage_share_floor' => 0.1, 'money.wage_share_cap' => 0.5]);
    config(['market.prices.wood' => ['floor' => 2, 'ceiling' => 5]]);
    [, $team, $tile] = foundedTeam();
    $building = builtProductionBuilding($tile, BuildingType::LumberCamp);
    $worker = teamMember($team);
    app(StartWork::class)->handle($worker, $building);

    // The shift finished hours ago; the player only logs back in now.
    $this->travel(12)->hours();
    $this->artisan('world:sweep')->assertSuccessful();

    // Default share 0.2 x (8 x 2) = 3.2 -> 3.
    expect($worker->fresh()->balance)->toBe(3);
});

test('the Mayor can set the wage share, clamped to the system cap', function () {
    config(['money.wage_share_floor' => 0.1, 'money.wage_share_cap' => 0.5]);
    [$owner, $team] = foundedTeam();

    $this->actingAs($owner)
        ->post('/team/wage-share', ['wage_share' => 0.9])
        ->assertRedirect();

    expect($team->fresh()->wage_share)->toBe(0.5);
});

test('the wage share is clamped up to the system floor', function () {
    config(['money.wage_share_floor' => 0.1, 'money.wage_share_cap' => 0.5]);
    [$owner, $team] = foundedTeam();

    $this->actingAs($owner)
        ->post('/team/wage-share', ['wage_share' => 0.01])
        ->assertRedirect();

    expect($team->fresh()->wage_share)->toBe(0.1);
});

test('an Officer cannot set the wage share (Mayor-only governance)', function () {
    [, $team] = foundedTeam();
    $officer = User::factory()->create();
    $team->memberships()->create(['user_id' => $officer->id, 'role' => TeamRole::Admin]);
    $officer->switchTeam($team);

    $this->actingAs($officer->fresh())
        ->post('/team/wage-share', ['wage_share' => 0.3])
        ->assertForbidden();
});

test('a Member cannot set the wage share', function () {
    [, $team] = foundedTeam();
    $member = teamMember($team);
    $member->switchTeam($team);

    $this->actingAs($member->fresh())
        ->post('/team/wage-share', ['wage_share' => 0.3])
        ->assertForbidden();
});

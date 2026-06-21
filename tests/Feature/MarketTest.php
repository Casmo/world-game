<?php

use App\Actions\Market\BuyFromMarket;
use App\Actions\Market\SellToMarket;
use App\Enums\ResourceType;
use App\Events\TreasuryChanged;
use App\Exceptions\InsufficientResourcesException;
use App\Exceptions\InsufficientTreasuryException;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;

test('a Mayor can sell Resources to the NPC, crediting the treasury at the floor price', function () {
    config(['money.seed_capital' => 0]);
    config(['market.prices.wood' => ['floor' => 2, 'ceiling' => 5]]);
    [, $team] = foundedTeam();
    $team->addResource(ResourceType::Wood, 10);

    app(SellToMarket::class)->handle($team, ResourceType::Wood, 4);

    expect($team->fresh()->treasury)->toBe(8)
        ->and($team->resourceTotal(ResourceType::Wood))->toBe(6);
});

test('selling more Resources than the Team holds is rejected and changes nothing', function () {
    config(['money.seed_capital' => 0]);
    config(['market.prices.wood' => ['floor' => 2, 'ceiling' => 5]]);
    [, $team] = foundedTeam();
    $team->addResource(ResourceType::Wood, 3);

    expect(fn () => app(SellToMarket::class)->handle($team, ResourceType::Wood, 5))
        ->toThrow(InsufficientResourcesException::class);

    expect($team->fresh()->treasury)->toBe(0)
        ->and($team->resourceTotal(ResourceType::Wood))->toBe(3);
});

test('a Mayor can buy Resources from the NPC, debiting the treasury at the ceiling price', function () {
    config(['money.seed_capital' => 100]);
    config(['market.prices.stone' => ['floor' => 3, 'ceiling' => 7]]);
    [, $team] = foundedTeam();

    app(BuyFromMarket::class)->handle($team, ResourceType::Stone, 5);

    expect($team->fresh()->treasury)->toBe(100 - 5 * 7)
        ->and($team->resourceTotal(ResourceType::Stone))->toBe(5);
});

test('buying more than the treasury can afford is rejected and never goes negative', function () {
    config(['money.seed_capital' => 20]);
    config(['market.prices.stone' => ['floor' => 3, 'ceiling' => 7]]);
    [, $team] = foundedTeam();

    // 3 x 7 = 21 > 20 treasury.
    expect(fn () => app(BuyFromMarket::class)->handle($team, ResourceType::Stone, 3))
        ->toThrow(InsufficientTreasuryException::class);

    expect($team->fresh()->treasury)->toBe(20)
        ->and($team->resourceTotal(ResourceType::Stone))->toBe(0);
});

test('a treasury change broadcasts to the Team', function () {
    Event::fake([TreasuryChanged::class]);
    config(['money.seed_capital' => 100]);
    config(['market.prices.wood' => ['floor' => 2, 'ceiling' => 5]]);
    [, $team] = foundedTeam();
    $team->addResource(ResourceType::Wood, 5);

    app(SellToMarket::class)->handle($team, ResourceType::Wood, 2);
    app(BuyFromMarket::class)->handle($team, ResourceType::Wood, 1);

    Event::assertDispatchedTimes(TreasuryChanged::class, 2);
    Event::assertDispatched(TreasuryChanged::class, fn (TreasuryChanged $e) => $e->team->is($team));
});

test('back-to-back purchases cannot overspend the treasury (atomic guard)', function () {
    config(['money.seed_capital' => 7]);
    config(['market.prices.stone' => ['floor' => 3, 'ceiling' => 7]]);
    [, $team] = foundedTeam();

    // The treasury affords exactly one purchase; the second must be refused, and
    // the conditional decrement can never drive it negative (ADR-0010).
    app(BuyFromMarket::class)->handle($team, ResourceType::Stone, 1);

    expect(fn () => app(BuyFromMarket::class)->handle($team, ResourceType::Stone, 1))
        ->toThrow(InsufficientTreasuryException::class);

    expect($team->fresh()->treasury)->toBe(0)
        ->and($team->resourceTotal(ResourceType::Stone))->toBe(1);
});

test('a Mayor can sell via the market endpoint', function () {
    config(['money.seed_capital' => 0]);
    config(['market.prices.wood' => ['floor' => 2, 'ceiling' => 5]]);
    [$owner, $team] = foundedTeam();
    $team->addResource(ResourceType::Wood, 10);

    $this->actingAs($owner)
        ->post('/market/sell', ['resource' => 'wood', 'quantity' => 4])
        ->assertRedirect();

    expect($team->fresh()->treasury)->toBe(8)
        ->and($team->resourceTotal(ResourceType::Wood))->toBe(6);
});

test('a Mayor can buy via the market endpoint', function () {
    config(['money.seed_capital' => 100]);
    config(['market.prices.wood' => ['floor' => 2, 'ceiling' => 5]]);
    [$owner, $team] = foundedTeam();

    $this->actingAs($owner)
        ->post('/market/buy', ['resource' => 'wood', 'quantity' => 3])
        ->assertRedirect();

    expect($team->fresh()->treasury)->toBe(85)
        ->and($team->resourceTotal(ResourceType::Wood))->toBe(3);
});

test('a Member cannot trade on the market', function () {
    config(['market.prices.wood' => ['floor' => 2, 'ceiling' => 5]]);
    [, $team] = foundedTeam();
    $member = teamMember($team);
    $member->switchTeam($team);

    $this->actingAs($member->fresh())
        ->post('/market/buy', ['resource' => 'wood', 'quantity' => 1])
        ->assertForbidden();
});

test('buying beyond the treasury via the endpoint is rejected', function () {
    config(['money.seed_capital' => 5]);
    config(['market.prices.stone' => ['floor' => 3, 'ceiling' => 7]]);
    [$owner] = foundedTeam();

    $this->actingAs($owner)
        ->post('/market/buy', ['resource' => 'stone', 'quantity' => 1])
        ->assertStatus(422);
});

test('the market page shows NPC prices, the Team stockpile, and the treasury', function () {
    $this->withoutVite();
    config(['money.seed_capital' => 250]);
    [$owner, $team] = foundedTeam();
    $team->addResource(ResourceType::Wood, 12);

    $this->actingAs($owner)
        ->get('/market')
        ->assertInertia(fn (Assert $page) => $page
            ->component('market')
            ->where('treasury', 250)
            ->has('prices', 3)
            ->where('stockpile.wood', 12)
        );
});

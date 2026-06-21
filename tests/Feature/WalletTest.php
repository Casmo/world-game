<?php

use App\Actions\Teams\CreateTeam;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('founding a Team seeds its treasury with the configured seed capital', function () {
    config(['money.seed_capital' => 500]);

    $team = app(CreateTeam::class)->handle(User::factory()->create(), 'Acme');

    expect($team->treasury)->toBe(500);
});

test('a new player starts with a zero personal balance', function () {
    expect(User::factory()->create()->balance)->toBe(0);
});

test('a player can view their own balance and their team treasury on the wallet', function () {
    config(['money.seed_capital' => 750]);
    $user = User::factory()->create();
    app(CreateTeam::class)->handle($user, 'Acme');
    $user->forceFill(['balance' => 120])->save();

    $this->withoutVite()
        ->actingAs($user->fresh())
        ->get('/wallet')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('wallet')
            ->where('balance', 120)
            ->where('treasury', 750)
        );
});

test('the wallet never exposes another team’s treasury', function () {
    config(['money.seed_capital' => 100]);
    $alice = User::factory()->create();
    app(CreateTeam::class)->handle($alice, 'Alpha');

    config(['money.seed_capital' => 999]);
    $bob = User::factory()->create();
    app(CreateTeam::class)->handle($bob, 'Beta');

    $this->withoutVite()
        ->actingAs($alice->fresh())
        ->get('/wallet')
        ->assertInertia(fn (Assert $page) => $page->where('treasury', 100));
});

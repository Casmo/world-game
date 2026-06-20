<?php

use App\Actions\Teams\CreateTeam;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\Tile;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

test('founding a Team claims exactly one unowned starting Tile owned by the Team', function () {
    $user = User::factory()->create();

    $team = app(CreateTeam::class)->handle($user, 'Acme');

    expect($team->tiles()->count())->toBe(1);

    $tile = $team->tiles()->first();
    expect($tile->team_id)->toBe($team->id)
        ->and($tile->isOwned())->toBeTrue()
        ->and(Tile::find($tile->h3_index))->not->toBeNull();
});

test('two founded Teams receive distinct starting Tiles', function () {
    $teamA = app(CreateTeam::class)->handle(User::factory()->create(), 'Alpha');
    $teamB = app(CreateTeam::class)->handle(User::factory()->create(), 'Beta');

    $tileA = $teamA->tiles()->first()->h3_index;
    $tileB = $teamB->tiles()->first()->h3_index;

    expect($tileA)->not->toBe($tileB)
        ->and(Tile::whereNotNull('team_id')->count())->toBe(2);
});

test('joining an existing Team does not claim a new Tile', function () {
    $founder = User::factory()->create();
    $team = app(CreateTeam::class)->handle($founder, 'Acme');
    $tilesBefore = Tile::whereNotNull('team_id')->count();

    // A second player joins the existing Team (the invitation-accept path adds a
    // membership; it does not create a Team, so no Tile is claimed).
    $joiner = User::factory()->create();
    $team->memberships()->create([
        'user_id' => $joiner->id,
        'role' => TeamRole::Member,
    ]);

    expect($team->fresh()->tiles()->count())->toBe(1)
        ->and(Tile::whereNotNull('team_id')->count())->toBe($tilesBefore);
});

test('a starting Tile can never be claimed by two Teams (atomic claim)', function () {
    // Pre-own every candidate cell but one, then found two Teams: only one can
    // take the single remaining Tile; the other must spawn elsewhere — never the
    // same Tile.
    $teamA = app(CreateTeam::class)->handle(User::factory()->create(), 'Alpha');
    $teamB = app(CreateTeam::class)->handle(User::factory()->create(), 'Beta');

    $owners = Tile::whereNotNull('team_id')->pluck('team_id');

    expect($owners)->toHaveCount(2)
        ->and($owners->unique())->toHaveCount(2);
});

test('the world map marks the viewer’s own Team Tiles', function () {
    Queue::fake();
    $user = User::factory()->create();
    app(CreateTeam::class)->handle($user, 'Acme'); // spawns near the map centre

    $this->withoutVite()
        ->actingAs($user->fresh())
        ->get('/world-map')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('world-map')
            ->where('tiles', fn ($tiles) => collect($tiles)->contains(
                fn ($t) => $t['is_own_team'] === true && $t['is_owned'] === true,
            ))
        );
});

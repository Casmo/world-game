<?php

use App\Actions\Tiles\MaterializeTile;
use App\Enums\TileResolutionStatus;
use App\Jobs\ResolveTileBiome;
use App\Models\Tile;
use App\Models\User;
use App\Support\BiomeApi;
use App\Support\H3;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->withoutVite());

test('visiting the world map materializes the surrounding Tiles as pending', function () {
    Queue::fake();

    $this->actingAs(User::factory()->create())
        ->get('/world-map')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('world-map')
            ->has('tiles')
            ->where('tiles.0.status', 'pending')
        );

    expect(Tile::count())->toBeGreaterThan(0)
        ->and(Tile::where('resolution_status', TileResolutionStatus::Pending)->count())
        ->toBe(Tile::count());
});

test('the world map dispatches biome resolution for each newly revealed Tile', function () {
    Queue::fake();

    $this->actingAs(User::factory()->create())->get('/world-map')->assertOk();

    Queue::assertPushed(ResolveTileBiome::class, Tile::count());
});

test('map props expose each Tile’s biome and terrain so it can be inspected', function () {
    Queue::fake();

    $this->actingAs(User::factory()->create())
        ->get('/world-map')
        ->assertInertia(fn (Assert $page) => $page
            ->has('tiles.0', fn (Assert $tile) => $tile
                ->has('h3_index')
                ->has('biome')
                ->has('terrain')
                ->has('base_resources')
                ->has('status')
                ->has('center')
                ->has('team_id')
                ->has('is_owned')
                ->has('is_own_team')
            )
        );
});

test('revealing the map twice does not recreate existing Tiles', function () {
    Queue::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->get('/world-map')->assertOk();
    $countAfterFirst = Tile::count();

    $this->actingAs($user)->get('/world-map')->assertOk();

    expect(Tile::count())->toBe($countAfterFirst);
});

test('materializing a Tile creates the row once and never re-dispatches once resolved', function () {
    Queue::fake();
    $action = app(MaterializeTile::class);
    $h3 = app(H3::class)->latLngToCell(52.3676, 4.9041, config('h3.resolution'));

    $action->handle($h3); // creates a pending Tile and dispatches resolution
    expect(Tile::where('h3_index', $h3)->count())->toBe(1);
    Queue::assertPushed(ResolveTileBiome::class, 1);

    // Once resolved, revealing it again creates nothing and dispatches nothing.
    Tile::find($h3)->update(['resolution_status' => TileResolutionStatus::Resolved]);
    $action->handle($h3);

    expect(Tile::where('h3_index', $h3)->count())->toBe(1);
    Queue::assertPushed(ResolveTileBiome::class, 1); // still just the first
});

test('the resolve job fetches the biome from the geo API and caches it', function () {
    Http::fake([
        '*' => Http::response(['biome' => 'desert', 'terrain' => 'dunes', 'base_resources' => ['stone' => 3]]),
    ]);
    $tile = Tile::factory()->create(); // pending

    (new ResolveTileBiome($tile))->handle(app(BiomeApi::class));

    expect($tile->fresh()->resolution_status)->toBe(TileResolutionStatus::Resolved)
        ->and($tile->fresh()->biome)->toBe('desert')
        ->and($tile->fresh()->terrain)->toBe('dunes')
        ->and($tile->fresh()->base_resources)->toBe(['stone' => 3]);

    Http::assertSentCount(1);
});

test('the resolve job is a no-op for an already-resolved Tile (API hit at most once per Tile)', function () {
    Http::fake([
        '*' => Http::response(['biome' => 'tundra', 'terrain' => 'flat', 'base_resources' => []]),
    ]);
    $tile = Tile::factory()->resolved()->create();

    (new ResolveTileBiome($tile))->handle(app(BiomeApi::class));

    Http::assertNothingSent();
});

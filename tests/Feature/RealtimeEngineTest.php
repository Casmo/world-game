<?php

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Events\ActivityCompleted;
use App\Exceptions\PlayerBusyException;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;

test('a player can start a Sleep activity', function () {
    $user = User::factory()->create();

    $activity = $user->startActivity(ActivityType::Sleep);

    expect($activity->isActive())->toBeTrue()
        ->and($activity->type)->toBe(ActivityType::Sleep)
        ->and($user->activeActivity()->is($activity))->toBeTrue();
});

test('a player may only perform one activity at a time', function () {
    $user = User::factory()->create();
    $user->startActivity(ActivityType::Sleep);

    expect(fn () => $user->startActivity(ActivityType::Sleep))
        ->toThrow(PlayerBusyException::class);

    expect($user->activities()->where('status', ActivityStatus::Active)->count())->toBe(1);
});

test('an active activity can be cancelled, freeing the player', function () {
    $user = User::factory()->create();
    $activity = $user->startActivity(ActivityType::Sleep);

    $activity->cancel();

    expect($activity->fresh()->status)->toBe(ActivityStatus::Cancelled)
        ->and($user->activeActivity())->toBeNull();
});

test('current energy is computed on read while sleeping, before any sweep runs', function () {
    $user = User::factory()->create();
    $user->energy = 20;
    $user->save();

    $user->startActivity(ActivityType::Sleep); // 8h to full restore

    // Halfway through the sleep, with no sweep having run yet.
    $this->travel(4)->hours();

    // 20 + (100 - 20) * 0.5 = 60
    expect($user->currentEnergy())->toBe(60)
        // ...and the stored base is untouched until completion.
        ->and($user->fresh()->energy)->toBe(20);
});

test('the sweep completes a due Sleep activity and restores energy to full', function () {
    Event::fake([ActivityCompleted::class]);

    $user = User::factory()->create();
    $user->energy = 10;
    $user->save();
    $activity = $user->startActivity(ActivityType::Sleep);

    $this->travel(9)->hours(); // past the 8h completion
    $this->artisan('world:sweep')->assertSuccessful();

    expect($activity->fresh()->status)->toBe(ActivityStatus::Completed)
        ->and($activity->fresh()->completed_at)->not->toBeNull()
        ->and($user->fresh()->energy)->toBe(User::MAX_ENERGY)
        ->and($user->fresh()->currentEnergy())->toBe(User::MAX_ENERGY);

    Event::assertDispatched(
        ActivityCompleted::class,
        fn (ActivityCompleted $e) => $e->activity->is($activity),
    );
});

test('the sweep is idempotent: re-running applies a completion only once', function () {
    Event::fake([ActivityCompleted::class]);

    $user = User::factory()->create();
    $user->startActivity(ActivityType::Sleep);

    $this->travel(9)->hours();
    $this->artisan('world:sweep')->assertSuccessful();
    $this->artisan('world:sweep')->assertSuccessful();

    expect(Activity::where('status', ActivityStatus::Completed)->count())->toBe(1);
    Event::assertDispatchedTimes(ActivityCompleted::class, 1);
});

test('the sweep catches up activities that completed long ago during downtime', function () {
    $user = User::factory()->create();
    $user->energy = 0;
    $user->save();
    $activity = $user->startActivity(ActivityType::Sleep);

    $this->travel(100)->days();
    $this->artisan('world:sweep')->assertSuccessful();

    expect($activity->fresh()->status)->toBe(ActivityStatus::Completed)
        ->and($user->fresh()->energy)->toBe(User::MAX_ENERGY);
});

test('the sweep is registered on the scheduler to run every minute', function () {
    $schedule = app(Schedule::class);

    $sweep = collect($schedule->events())
        ->first(fn ($event) => str_contains((string) $event->command, 'world:sweep'));

    expect($sweep)->not->toBeNull()
        ->and($sweep->expression)->toBe('* * * * *');
});

test('energy never exceeds the maximum and is never negative', function () {
    $user = User::factory()->create();
    $user->energy = User::MAX_ENERGY;
    $user->save();

    $user->startActivity(ActivityType::Sleep);
    $this->travel(20)->hours();

    expect($user->currentEnergy())->toBe(User::MAX_ENERGY);
});

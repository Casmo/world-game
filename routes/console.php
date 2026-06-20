<?php

use App\Models\TeamInvitation;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    TeamInvitation::query()
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now())
        ->delete();
})->daily()->description('Delete expired team invitations');

// Advance the persistent world: apply every due Activity completion (ADR-0007).
Schedule::command('world:sweep')
    ->everyMinute()
    ->withoutOverlapping()
    ->description('Apply due world Activity completions');

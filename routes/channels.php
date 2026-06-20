<?php

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// A player's private channel: only the player themselves may listen (ADR-0009).
Broadcast::channel('user.{userId}', function (User $user, int $userId) {
    return $user->id === $userId;
});

// A Team's private channel: only members of the Team may listen (ADR-0009).
Broadcast::channel('team.{teamId}', function (User $user, int $teamId) {
    $team = Team::find($teamId);

    return $team !== null && $user->belongsToTeam($team);
});

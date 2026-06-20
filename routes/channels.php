<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// A player's private channel: only the player themselves may listen (ADR-0009).
Broadcast::channel('user.{userId}', function (User $user, int $userId) {
    return $user->id === $userId;
});

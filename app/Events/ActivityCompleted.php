<?php

namespace App\Events;

use App\Models\Activity;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A thin signal that one of a player's Activities has completed. Carries only
 * identifiers (ADR-0009) — the client reloads authoritative state over HTTP.
 */
class ActivityCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Activity $activity) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->activity->user_id),
        ];
    }

    /**
     * The thin payload: just what's needed for the client to know what to reload.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'activity_id' => $this->activity->id,
            'type' => $this->activity->type->value,
        ];
    }
}

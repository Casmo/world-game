<?php

namespace App\Events;

use App\Models\Building;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A thin signal that a Building finished construction. Broadcast to the owning
 * Team so members' clients reload the City over HTTP (ADR-0009).
 */
class BuildingConstructed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Building $building) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('team.'.$this->building->tile->team_id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'building_id' => $this->building->id,
            'tile_id' => $this->building->tile_id,
            'type' => $this->building->type->value,
        ];
    }
}

<?php

namespace App\Events;

use App\Enums\BuildingType;
use App\Models\Team;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A thin signal that a Team's Research progressed toward its target. Broadcast
 * to the Team so members' clients refresh the tech tree (ADR-0009).
 */
class ResearchProgressed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Team $team, public BuildingType $target, public int $progress) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('team.'.$this->team->id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'target' => $this->target->value,
            'progress' => $this->progress,
            'cost' => $this->target->researchCost(),
        ];
    }
}

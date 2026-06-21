<?php

namespace App\Events;

use App\Models\Attack;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A thin alert that an attack is marching toward a Team's Tile, with its arrival
 * time. Broadcast to the defending Team (ADR-0009). Exact attacker composition
 * is withheld (Fog of war).
 */
class IncomingAttack implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $defenderTeamId, public Attack $attack) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('team.'.$this->defenderTeamId),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'target_tile_id' => $this->attack->target_tile_id,
            'arrives_at' => $this->attack->arrives_at->toIso8601String(),
        ];
    }
}

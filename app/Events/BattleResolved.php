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
 * A thin signal that a battle resolved. Broadcast to both the attacking and the
 * defending Team so each can fetch its own battle report (ADR-0009).
 */
class BattleResolved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Attack $attack, public ?int $defenderTeamId) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('team.'.$this->attack->attacker_team_id)];

        if ($this->defenderTeamId !== null) {
            $channels[] = new PrivateChannel('team.'.$this->defenderTeamId);
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'attack_id' => $this->attack->id,
            'attacker_won' => (bool) ($this->attack->report['attacker_won'] ?? false),
        ];
    }
}

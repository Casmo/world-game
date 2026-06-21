<?php

namespace App\Actions\War;

use App\Enums\AttackStatus;
use App\Enums\UnitStatus;
use App\Events\IncomingAttack;
use App\Exceptions\CannotAttackException;
use App\Models\Attack;
use App\Models\Team;
use App\Models\Tile;
use App\Support\H3;
use Illuminate\Support\Facades\DB;

/**
 * Send a composition of Units to attack a target Tile (ADR-0005). The force
 * marches for a duration derived from the H3 grid distance (#6); the sweep
 * resolves the battle on arrival.
 */
class LaunchAttack
{
    public function __construct(private H3 $h3) {}

    /**
     * @param  array<string, int>  $composition  unit-type value => count
     *
     * @throws CannotAttackException
     */
    public function handle(Team $attacker, Tile $target, array $composition): Attack
    {
        $origin = $attacker->tiles()->first();

        if ($origin === null) {
            throw new CannotAttackException('Your Team has no Tile to march from.');
        }

        if ($target->team_id === $attacker->id) {
            throw new CannotAttackException('You cannot attack your own Tile.');
        }

        return DB::transaction(function () use ($attacker, $origin, $target, $composition) {
            $distance = max(1, $this->h3->gridDistance($origin->h3_index, $target->h3_index));
            $marchSeconds = $distance * (int) config('war.march_seconds_per_ring');

            $attack = Attack::create([
                'attacker_team_id' => $attacker->id,
                'origin_tile_id' => $origin->h3_index,
                'target_tile_id' => $target->h3_index,
                'status' => AttackStatus::Marching,
                'march_seconds' => $marchSeconds,
                'arrives_at' => now()->addSeconds($marchSeconds),
            ]);

            foreach ($composition as $typeValue => $count) {
                $units = $attacker->units()
                    ->where('type', $typeValue)
                    ->whereIn('status', [UnitStatus::Idle, UnitStatus::Garrisoned])
                    ->limit($count)
                    ->get();

                if ($units->count() < $count) {
                    throw new CannotAttackException('Not enough available Units of that type.');
                }

                foreach ($units as $unit) {
                    $unit->update(['status' => UnitStatus::InTransit, 'attack_id' => $attack->id]);
                }
            }

            if ($target->team_id !== null) {
                IncomingAttack::dispatch($target->team_id, $attack);
            }

            return $attack;
        });
    }
}

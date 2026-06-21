<?php

namespace App\Actions\War;

use App\Enums\AttackStatus;
use App\Enums\UnitStatus;
use App\Events\BattleResolved;
use App\Models\Attack;
use App\Models\Tile;
use App\Models\Unit;
use App\Support\CombatResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Resolve an arrived attack (ADR-0005). On a marching arrival it runs the
 * combat-resolution service and applies the raiding-tier consequences (loot or
 * losses) atomically; on a returning arrival it brings survivors home. Always
 * called from the sweep inside a locking transaction (ADR-0010).
 */
class ResolveAttack
{
    public function __construct(private CombatResolver $resolver) {}

    /**
     * Resolve the battle for a freshly-arrived marching attack.
     */
    public function resolveArrival(Attack $attack): void
    {
        DB::transaction(function () use ($attack) {
            $attack = Attack::query()->whereKey($attack->getKey())->lockForUpdate()->first();
            if ($attack === null || $attack->status !== AttackStatus::Marching) {
                return;
            }

            $attackers = $this->composition($attack->units()->get());

            $target = Tile::find($attack->target_tile_id);
            $defenderTeam = $target?->team;
            $garrison = $target
                ? Unit::query()->where('tile_id', $target->h3_index)->where('status', UnitStatus::Garrisoned)->get()
                : collect();

            $outcome = $this->resolver->resolve($attackers, $this->composition($garrison));

            $loot = [];

            if ($outcome->attackerWins) {
                if ($defenderTeam !== null) {
                    foreach ($defenderTeam->resources()->where('amount', '>', 0)->get() as $resource) {
                        $amount = (int) floor($resource->amount * $outcome->lootFraction);
                        if ($amount > 0 && $defenderTeam->removeResource($resource->type, $amount)) {
                            $attack->attacker->addResource($resource->type, $amount);
                            $loot[$resource->type->value] = $amount;
                        }
                    }
                }

                // Survivors march home.
                $attack->update([
                    'status' => AttackStatus::Returning,
                    'arrives_at' => now()->addSeconds($attack->march_seconds),
                    'report' => ['attacker_won' => true, 'loot' => $loot],
                ]);
            } else {
                // The attacking force is lost; the attacker forfeits Money to the defender.
                $attack->units()->delete();

                $penalty = (int) config('war.attack_failure_penalty');
                $payable = min($penalty, $attack->attacker->treasury);
                if ($payable > 0 && $attack->attacker->withdrawTreasury($payable)) {
                    $defenderTeam?->depositTreasury($payable);
                }

                $attack->update([
                    'status' => AttackStatus::Resolved,
                    'report' => ['attacker_won' => false, 'units_lost' => $attackers, 'money_lost' => $payable],
                ]);
            }

            BattleResolved::dispatch($attack, $defenderTeam?->id);
        });
    }

    /**
     * Bring a returning attack's survivors home and conclude it.
     */
    public function resolveReturn(Attack $attack): void
    {
        DB::transaction(function () use ($attack) {
            $attack = Attack::query()->whereKey($attack->getKey())->lockForUpdate()->first();
            if ($attack === null || $attack->status !== AttackStatus::Returning) {
                return;
            }

            $attack->units()->update([
                'status' => UnitStatus::Idle,
                'tile_id' => $attack->origin_tile_id,
                'attack_id' => null,
            ]);

            $attack->update(['status' => AttackStatus::Resolved]);
        });
    }

    /**
     * Reduce a set of Units to counts keyed by unit-type value.
     *
     * @param  Collection<int, Unit>  $units
     * @return array<string, int>
     */
    private function composition($units): array
    {
        return $units
            ->groupBy(fn (Unit $unit): string => $unit->type->value)
            ->map(fn ($group): int => $group->count())
            ->toArray();
    }
}

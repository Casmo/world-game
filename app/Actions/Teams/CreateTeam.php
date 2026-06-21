<?php

namespace App\Actions\Teams;

use App\Actions\Tiles\ClaimStartingTile;
use App\Enums\BuildingType;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateTeam
{
    public function __construct(private ClaimStartingTile $claimStartingTile) {}

    /**
     * Create a new team, add the user as owner, and claim its starting Tile.
     */
    public function handle(User $user, string $name, bool $isPersonal = false): Team
    {
        return DB::transaction(function () use ($user, $name, $isPersonal) {
            $team = Team::create([
                'name' => $name,
                'is_personal' => $isPersonal,
            ]);

            // Seed capital: a new Team starts with a founding treasury (ADR-0006).
            $team->forceFill(['treasury' => config('money.seed_capital')])->save();

            $team->memberships()->create([
                'user_id' => $user->id,
                'role' => TeamRole::Owner,
            ]);

            $user->switchTeam($team);

            // Unlock the default tech-tree set so the Team can build from day one (ADR-0003).
            foreach (config('techtree.default_unlocked') as $type) {
                $team->unlockBuilding(BuildingType::from($type));
            }

            // Founding a Team spawns it on an unowned starting Tile (ADR-0002).
            // Joining an existing Team happens via invitations, not here, so it
            // correctly claims no Tile.
            $this->claimStartingTile->handle($team);

            return $team;
        });
    }
}

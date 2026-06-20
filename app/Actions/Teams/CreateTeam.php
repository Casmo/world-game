<?php

namespace App\Actions\Teams;

use App\Actions\Tiles\ClaimStartingTile;
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

            $team->memberships()->create([
                'user_id' => $user->id,
                'role' => TeamRole::Owner,
            ]);

            $user->switchTeam($team);

            // Founding a Team spawns it on an unowned starting Tile (ADR-0002).
            // Joining an existing Team happens via invitations, not here, so it
            // correctly claims no Tile.
            $this->claimStartingTile->handle($team);

            return $team;
        });
    }
}

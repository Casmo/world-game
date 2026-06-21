<?php

namespace App\Http\Controllers;

use App\Actions\War\LaunchAttack;
use App\Enums\TeamRole;
use App\Enums\UnitType;
use App\Exceptions\CannotAttackException;
use App\Models\Tile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttackController extends Controller
{
    /**
     * Launch an attack on a target Tile (Mayor/Officer only).
     */
    public function store(Request $request, LaunchAttack $launchAttack): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($team === null, 404);

        $role = $request->user()->teamRole($team);
        abort_unless($role !== null && $role->isAtLeast(TeamRole::Admin), 403);

        $data = $request->validate([
            'target' => ['required', 'string', 'exists:tiles,h3_index'],
            'units' => ['required', 'array'],
            'units.*' => ['integer', 'min:1'],
        ]);

        try {
            $composition = [];
            foreach ($data['units'] as $type => $count) {
                $composition[UnitType::from($type)->value] = (int) $count;
            }

            $launchAttack->handle($team, Tile::findOrFail($data['target']), $composition);
        } catch (CannotAttackException|\ValueError $e) {
            abort(422, $e->getMessage());
        }

        return back();
    }
}

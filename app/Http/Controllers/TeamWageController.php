<?php

namespace App\Http\Controllers;

use App\Actions\Teams\SetWageShare;
use App\Enums\TeamRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TeamWageController extends Controller
{
    /**
     * Set the Team's wage share. Mayor-only — wage-setting is a governance lever
     * deliberately kept off the Officer kit (ADR-0006).
     */
    public function __invoke(Request $request, SetWageShare $setWageShare): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($team === null, 404);
        abort_unless($request->user()->teamRole($team) === TeamRole::Owner, 403);

        $data = $request->validate([
            'wage_share' => ['required', 'numeric', 'min:0', 'max:1'],
        ]);

        $setWageShare->handle($team, (float) $data['wage_share']);

        return back();
    }
}

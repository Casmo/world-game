<?php

namespace App\Http\Controllers;

use App\Enums\TeamRole;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WalletController extends Controller
{
    /**
     * Show the player's wallet: their own balance and their Team's treasury.
     * Only ever the viewer's own data (finances are private).
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $team = $user->currentTeam;

        abort_if($team === null, 404);

        return Inertia::render('wallet', [
            'balance' => $user->balance,
            'treasury' => $team->treasury,
            'team' => ['name' => $team->name],
            'wageShare' => $team->wage_share,
            // The wage share is a Mayor-only governance lever (ADR-0006).
            'canSetWageShare' => $user->teamRole($team) === TeamRole::Owner,
        ]);
    }
}

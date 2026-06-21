<?php

namespace App\Http\Controllers;

use App\Support\TechTree;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TechTreeController extends Controller
{
    /**
     * Show the tech tree from the current Team's perspective: every Building
     * type as unlocked, available, or locked (Fog of war — a Team only sees its
     * own unlocked set).
     */
    public function __invoke(Request $request, TechTree $techTree): Response
    {
        $team = $request->user()->currentTeam;
        abort_if($team === null, 404);

        return Inertia::render('tech-tree', [
            'buildings' => $techTree->forTeam($team),
        ]);
    }
}

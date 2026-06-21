<?php

namespace App\Http\Controllers;

use App\Actions\Market\BuyFromMarket;
use App\Actions\Market\SellToMarket;
use App\Enums\ResourceType;
use App\Enums\TeamRole;
use App\Exceptions\InsufficientResourcesException;
use App\Exceptions\InsufficientTreasuryException;
use App\Models\Team;
use App\Support\MarketCatalogue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MarketController extends Controller
{
    /**
     * Show the NPC world market: prices, the Team's stockpile, and its treasury
     * (only ever the viewer's own Team — finances are private).
     */
    public function index(Request $request, MarketCatalogue $catalogue): Response
    {
        $team = $this->currentTeam($request);

        return Inertia::render('market', [
            'prices' => $catalogue->all(),
            'treasury' => $team->treasury,
            'stockpile' => $team->resources->mapWithKeys(
                fn ($resource) => [$resource->type->value => $resource->amount]
            ),
        ]);
    }

    /**
     * Sell the Team's Resources to the NPC at the floor price (Mayor/Officer only).
     */
    public function sell(Request $request, SellToMarket $sell): RedirectResponse
    {
        [$team, $type, $quantity] = $this->authorizedTrade($request);

        try {
            $sell->handle($team, $type, $quantity);
        } catch (InsufficientResourcesException $e) {
            abort(422, $e->getMessage());
        }

        return back();
    }

    /**
     * Buy Resources from the NPC at the ceiling price (Mayor/Officer only).
     */
    public function buy(Request $request, BuyFromMarket $buy): RedirectResponse
    {
        [$team, $type, $quantity] = $this->authorizedTrade($request);

        try {
            $buy->handle($team, $type, $quantity);
        } catch (InsufficientTreasuryException $e) {
            abort(422, $e->getMessage());
        }

        return back();
    }

    /**
     * Validate the trade request and authorize the actor as a Mayor/Officer of
     * their current Team.
     *
     * @return array{0: Team, 1: ResourceType, 2: int}
     */
    private function authorizedTrade(Request $request): array
    {
        $team = $this->currentTeam($request);

        $role = $request->user()->teamRole($team);
        abort_unless($role !== null && $role->isAtLeast(TeamRole::Admin), 403);

        $data = $request->validate([
            'resource' => ['required', Rule::enum(ResourceType::class)],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        return [$team, ResourceType::from($data['resource']), (int) $data['quantity']];
    }

    private function currentTeam(Request $request): Team
    {
        $team = $request->user()->currentTeam;
        abort_if($team === null, 404);

        return $team;
    }
}

<?php

use App\Http\Controllers\AttackController;
use App\Http\Controllers\BuildingConstructionController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\BuildingWorkController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\ResearchTargetController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\TeamWageController;
use App\Http\Controllers\TechTreeController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WorldMapController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });

Route::middleware(['auth'])->group(function () {
    Route::get('world-map', WorldMapController::class)->name('world-map');
    Route::get('wallet', WalletController::class)->name('wallet');
    Route::get('tech-tree', TechTreeController::class)->name('tech-tree');

    Route::get('market', [MarketController::class, 'index'])->name('market');
    Route::post('market/sell', [MarketController::class, 'sell'])->name('market.sell');
    Route::post('market/buy', [MarketController::class, 'buy'])->name('market.buy');

    Route::post('team/wage-share', TeamWageController::class)->name('team.wage-share');
    Route::post('research/target', ResearchTargetController::class)->name('research.target');

    Route::get('units', [UnitController::class, 'index'])->name('units');
    Route::post('units', [UnitController::class, 'store'])->name('units.store');
    Route::delete('units', [UnitController::class, 'destroy'])->name('units.destroy');
    Route::post('attacks', [AttackController::class, 'store'])->name('attacks.store');

    Route::get('tiles/{tile}/city', CityController::class)->name('city.show');
    Route::post('buildings', [BuildingController::class, 'store'])->name('buildings.store');
    Route::post('buildings/{building}/construct', BuildingConstructionController::class)->name('buildings.construct');
    Route::post('buildings/{building}/work', BuildingWorkController::class)->name('buildings.work');

    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';

<?php

use App\Http\Controllers\BuildingConstructionController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\BuildingWorkController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Teams\TeamInvitationController;
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

    Route::get('tiles/{tile}/city', CityController::class)->name('city.show');
    Route::post('buildings', [BuildingController::class, 'store'])->name('buildings.store');
    Route::post('buildings/{building}/construct', BuildingConstructionController::class)->name('buildings.construct');
    Route::post('buildings/{building}/work', BuildingWorkController::class)->name('buildings.work');

    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';

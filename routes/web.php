<?php

use App\Http\Controllers\Api\MonsterSearchController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CampaignMemberController;
use App\Http\Controllers\CharacterController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NpcController;
use App\Http\Controllers\InitiativeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MonsterController;
use App\Http\Controllers\SpellController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/locale/{locale}', LocaleController::class)->name('locale.switch');

Route::get('/initiative', InitiativeController::class)->name('initiative');
Route::get('/api/monsters/search', MonsterSearchController::class)->name('api.monsters.search');

Route::resource('monsters', MonsterController::class)->only(['index', 'show']);
Route::resource('spells', SpellController::class)->only(['index', 'show']);

// Authentication routes (guests only)
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Campaign creation (must be registered before the resource show route)
    Route::resource('campaigns', CampaignController::class)->only(['create', 'store']);

    // Campaign member management
    Route::post('/campaigns/{campaign}/members', [CampaignMemberController::class, 'store'])
        ->middleware('campaign.role:mj')
        ->name('campaigns.members.store');

    Route::delete('/campaigns/{campaign}/members/{user}', [CampaignMemberController::class, 'destroy'])
        ->middleware('campaign.role:mj')
        ->name('campaigns.members.destroy');

    Route::delete('/campaigns/{campaign}/leave', [CampaignMemberController::class, 'leave'])
        ->name('campaigns.leave');

    // Character creation (MJ + Joueur)
    Route::get('/campaigns/{campaign}/characters/create', [CharacterController::class, 'create'])
        ->middleware('campaign.role:mj,joueur')
        ->name('campaigns.characters.create');

    Route::post('/campaigns/{campaign}/characters', [CharacterController::class, 'store'])
        ->middleware('campaign.role:mj,joueur')
        ->name('campaigns.characters.store');

    Route::get('/campaigns/{campaign}/characters/{character}', [CharacterController::class, 'show'])
        ->middleware('campaign.role:mj,joueur')
        ->name('campaigns.characters.show');

    // NPC creation (MJ only)
    Route::get('/campaigns/{campaign}/npcs/create', [NpcController::class, 'create'])
        ->middleware('campaign.role:mj')
        ->name('campaigns.npcs.create');

    Route::post('/campaigns/{campaign}/npcs', [NpcController::class, 'store'])
        ->middleware('campaign.role:mj')
        ->name('campaigns.npcs.store');
});

// Public campaign routes (index/show — after auth group so /campaigns/create takes priority)
Route::resource('campaigns', CampaignController::class)->only(['index', 'show']);

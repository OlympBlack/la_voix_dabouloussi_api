<?php

use App\Http\Controllers\Api\Admin\AlbumController as AdminAlbumController;
use App\Http\Controllers\Api\Admin\CategorieController as AdminCategorieController;
use App\Http\Controllers\Api\Admin\ContactController as AdminContactController;
use App\Http\Controllers\Api\Admin\ContenuController as AdminContenuController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\MarketController as AdminMarketController;
use App\Http\Controllers\Api\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Api\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AlbumController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContenuController;
use App\Http\Controllers\Api\MarketController;
use App\Http\Controllers\Api\SiteController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login']);
    Route::middleware('api.auth')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::patch('me', [AuthController::class, 'update']);
        Route::patch('me/password', [AuthController::class, 'updatePassword']);
    });
});

Route::get('site/settings', [SiteController::class, 'settings']);
Route::get('home', [SiteController::class, 'home']);
Route::get('search', [SiteController::class, 'search']);
Route::post('contact', [SiteController::class, 'contact']);

Route::get('journaux', [ContenuController::class, 'journaux']);
Route::get('journaux/{contenu}', [ContenuController::class, 'show']);
Route::get('jda', [ContenuController::class, 'jda']);
Route::get('jda/{contenu}', [ContenuController::class, 'show']);
Route::get('parlement', [ContenuController::class, 'parlement']);
Route::get('parlement/{contenu}', [ContenuController::class, 'show']);
Route::get('commissions', [ContenuController::class, 'commissions']);
Route::get('contes', [ContenuController::class, 'contes']);
Route::get('contes/{contenu}', [ContenuController::class, 'show']);
Route::get('poemes', [ContenuController::class, 'poemes']);
Route::get('poemes/{contenu}', [ContenuController::class, 'show']);
Route::get('actualites', [ContenuController::class, 'actualites']);
Route::get('actualites/{contenu}', [ContenuController::class, 'show']);
Route::get('pages/{contenu}', [ContenuController::class, 'show']);
Route::get('contenus/{contenu}', [ContenuController::class, 'show']);

Route::get('albums', [AlbumController::class, 'index']);
Route::get('albums/{album}', [AlbumController::class, 'show']);

Route::get('market/rayons', [MarketController::class, 'rayons']);
Route::get('market/produits', [MarketController::class, 'produits']);
Route::post('market/jeu', [MarketController::class, 'jouer']);
Route::get('market/livebets', [MarketController::class, 'livebets']);

Route::prefix('admin')->middleware('api.auth')->group(function (): void {
    Route::get('dashboard', [AdminDashboardController::class, 'index']);
});

Route::prefix('admin')->middleware(['api.auth', 'permission:manage_content'])->group(function (): void {
    Route::get('contenus', [AdminContenuController::class, 'index']);
    Route::post('contenus', [AdminContenuController::class, 'store']);
    Route::get('contenus/{contenu}', [AdminContenuController::class, 'show']);
    Route::put('contenus/{contenu}', [AdminContenuController::class, 'update']);
    Route::patch('contenus/{contenu}', [AdminContenuController::class, 'update']);
    Route::delete('contenus/{contenu}', [AdminContenuController::class, 'destroy']);
    Route::patch('contenus/{contenu}/statut', [AdminContenuController::class, 'statut']);

    Route::apiResource('categories', AdminCategorieController::class)->except(['show']);
    Route::apiResource('medias', AdminMediaController::class)->except(['show', 'update']);
    Route::post('upload', [AdminMediaController::class, 'upload']);
    Route::apiResource('albums', AdminAlbumController::class)->except(['show']);
    Route::post('albums/{album}/photos', [AdminAlbumController::class, 'addPhoto']);
    Route::delete('albums/{album}/photos/{photo}', [AdminAlbumController::class, 'deletePhoto']);

    Route::get('contacts', [AdminContactController::class, 'index']);
    Route::patch('contacts/{contact}', [AdminContactController::class, 'update']);
    Route::delete('contacts/{contact}', [AdminContactController::class, 'destroy']);

    Route::get('settings', [AdminSettingController::class, 'index']);
    Route::put('settings/{setting}', [AdminSettingController::class, 'update']);

    Route::get('market/produits', [AdminMarketController::class, 'produits']);
    Route::post('market/produits', [AdminMarketController::class, 'storeProduit']);
    Route::delete('market/produits/{produit}', [AdminMarketController::class, 'destroyProduit']);
    Route::get('market/rayons', [AdminMarketController::class, 'rayons']);
});

Route::prefix('admin')->middleware(['api.auth', 'permission:manage_users'])->group(function (): void {
    Route::get('users', [AdminUserController::class, 'index']);
    Route::post('users', [AdminUserController::class, 'store']);
    Route::put('users/{user}', [AdminUserController::class, 'update']);
    Route::patch('users/{user}', [AdminUserController::class, 'update']);
    Route::delete('users/{user}', [AdminUserController::class, 'destroy']);
    Route::get('roles', [AdminUserController::class, 'roles']);
    Route::get('permissions', [AdminUserController::class, 'permissions']);
});

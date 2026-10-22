<?php

use App\Http\Controllers\Auth\GitHubOAuthController;
use App\Http\Controllers\Identity\MembershipController;
use App\Http\Controllers\Artifacts\ArtifactController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/auth/github/redirect', [GitHubOAuthController::class, 'redirect'])
    ->middleware('guest')
    ->name('auth.github.redirect');
Route::get('/auth/github/callback', [GitHubOAuthController::class, 'callback'])
    ->middleware('guest')
    ->name('auth.github.callback');
Route::post('/logout', [GitHubOAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');
Route::get('/dashboard', static fn () => response('DevGuard AI'))
    ->middleware('auth')
    ->name('dashboard');

Route::middleware('auth')->group(function (): void {
    Route::post('/artifacts/{artifact}/access-links', [ArtifactController::class, 'createAccessLink'])->whereUlid('artifact');
    Route::get('/artifacts/{artifact}/content', [ArtifactController::class, 'content'])->whereUlid('artifact');
    Route::delete('/artifacts/{artifact}/access-links/{link}', [ArtifactController::class, 'revokeAccessLink'])->whereUlid('artifact')->whereUlid('link');
    Route::post('/workspaces/{workspace}/invitations', [MembershipController::class, 'storeInvitation']);
    Route::post('/membership-invitations/accept', [MembershipController::class, 'acceptInvitation']);
    Route::patch('/workspaces/{workspace}/members/{user}', [MembershipController::class, 'updateRole'])
        ->whereNumber('user');
    Route::delete('/workspaces/{workspace}/members/{user}', [MembershipController::class, 'destroyMembership'])
        ->whereNumber('user');
    Route::post('/workspaces/{workspace}/ownership-transfers', [MembershipController::class, 'storeOwnershipTransfer']);
    Route::post('/ownership-transfers/{transfer}/accept', [MembershipController::class, 'acceptOwnershipTransfer'])
        ->whereUlid('transfer');
});

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedController;
use App\Http\Controllers\Auth\PermissionController;
use App\Http\Controllers\Auth\UserController;

// Authenticated User: Login
Route::post('/login', [AuthenticatedController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    // Authenticated User: Logout
    Route::post('/logout', [AuthenticatedController::class, 'destroy']);

    // Users
    Route::resource('/users', UserController::class)->except('create', 'edit');
    Route::post('/users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');
    Route::delete('/users/{user}/force', [UserController::class, 'force'])->name('users.force');

    // Permissions
    Route::resource('/permissions', PermissionController::class)->except('create', 'edit');
});

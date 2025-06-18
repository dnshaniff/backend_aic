<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedController;
use App\Http\Controllers\Auth\PermissionController;
use App\Http\Controllers\Auth\RoleController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EmployeeController;

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

    // Roles
    Route::resource('/roles', RoleController::class)->except('create', 'edit');

    // Employees
    Route::resource('/employees', EmployeeController::class)->except('create', 'edit');
    Route::post('/employees/{employee}/restore', [EmployeeController::class, 'restore'])->name('employees.restore');
    Route::delete('/employees/{employee}/force', [EmployeeController::class, 'force'])->name('employees.force');

    // Categories
    Route::resource('/categories', CategoryController::class)->except('create', 'edit');
    Route::post('/categories/{category}/restore', [CategoryController::class, 'restore'])->name('categories.restore');
    Route::delete('/categories/{category}/force', [CategoryController::class, 'force'])->name('categories.force');
});

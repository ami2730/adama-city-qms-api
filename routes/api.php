<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CounterController;
use App\Http\Controllers\ServiceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider within the "api" middleware group.
| All routes are prefixed with /api/
|
*/

// ────────────────────────────────────────────────
// Public / Kiosk Routes (No authentication required)
// ────────────────────────────────────────────────
Route::prefix('public')->group(function () {

    // Authentication
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);

    // Queue & Display (Kiosk / Public screens)
    Route::post('/queue',           [TicketController::class, 'getQueue']);
    Route::post('/tickets',         [TicketController::class, 'store']);     // Create new ticket
    Route::get('/tickets/{number}', [TicketController::class, 'show']);      // View ticket status

    // Read-only resources
    Route::get('/branches',         [BranchController::class, 'index']);
    Route::get('/branches/{id}',    [BranchController::class, 'show']);
    Route::get('/services',         [ServiceController::class, 'index']);
    Route::get('/services/{id}',    [ServiceController::class, 'show']);
});

// ────────────────────────────────────────────────
// Authenticated Routes (Any logged-in user)
// ────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me',         [AuthController::class, 'me']);
    Route::post('/logout',    [AuthController::class, 'logout']);
    Route::post('/logout-all',[AuthController::class, 'logoutAll']);
    Route::post('/refresh',   [AuthController::class, 'refresh']);

    // Ticket listing (for authenticated users – possibly staff dashboard)
    Route::get('/tickets',    [TicketController::class, 'index']);
});

// ────────────────────────────────────────────────
// Staff & Admin Protected Routes
// ────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:staff,admin'])->group(function () {

    // Queue Management Actions
    Route::post('/tickets/call-next',           [TicketController::class, 'callNext']);
    Route::post('/tickets/{ticket}/serve',      [TicketController::class, 'serve']);
    Route::post('/tickets/{ticket}/skip',       [TicketController::class, 'skip']);

    // Counter listing (for staff to see their assigned counters, etc.)
    Route::get('/counters', [CounterController::class, 'index']);
});

// ────────────────────────────────────────────────
// Admin + Staff CRUD Routes
// ────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:admin,staff'])->group(function () {

    // Branches CRUD
    Route::apiResource('branches', BranchController::class)->only([
        'store', 'update', 'destroy'
    ]);
    Route::patch('/branches/{branch}', [BranchController::class, 'update']); // extra PATCH support

    // Counters CRUD
    Route::apiResource('counters', CounterController::class)->only([
        'store', 'show', 'update', 'destroy'
    ]);
    Route::patch('/counters/{counter}', [CounterController::class, 'update']);

    // Services CRUD
    Route::apiResource('services', ServiceController::class)->only([
        'store', 'update', 'destroy'
    ]);
    Route::patch('/services/{service}', [ServiceController::class, 'update']);

    // Users Management (admin/staff can manage other users)
    Route::get('/users',              [AuthController::class, 'listUsers']);
    Route::put('/users/{user}',       [AuthController::class, 'updateUser']);
    Route::delete('/users/{user}',    [AuthController::class, 'deleteUser']);
});

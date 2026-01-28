<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CounterController;
use App\Http\Controllers\ServiceController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public Routes (Accessible by Kiosk)
Route::post('/queue', [TicketController::class, 'getQueue']);
Route::get('/branches', [BranchController::class, 'index']);
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/branches/{id}', [BranchController::class, 'show']);
Route::get('/services/{id}', [ServiceController::class, 'show']);
Route::get('/tickets/{number}', [TicketController::class, 'show']);
Route::post('/tickets', [TicketController::class, 'store']); // Create ticket
 Route::get('/tickets', [TicketController::class, 'index']); 
// Staff & Admin Routes
Route::middleware(['auth:sanctum', 'role:staff,admin'])->group(function () {
    Route::post('/tickets/call-next', [TicketController::class, 'callNext']);
    Route::post('/tickets/{ticket}/serve', [TicketController::class, 'serve']);
    Route::post('/tickets/{ticket}/skip', [TicketController::class, 'skip']);
    Route::get('/counters', [CounterController::class, 'index']);
   
});

// Authenticated Routes (All Roles)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/me',          [AuthController::class, 'me']);
    Route::post('/logout',     [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    Route::post('/refresh',    [AuthController::class, 'refresh']);
});


Route::middleware(['auth:sanctum', 'role:admin,staff'])->group(function () {
    // Branch CRUD
    Route::post('/branches', [BranchController::class, 'store']);
    Route::put('/branches/{id}', [BranchController::class, 'update']);
    Route::patch('/branches/{id}', [BranchController::class, 'update']);
    Route::delete('/branches/{id}', [BranchController::class, 'destroy']);

    // Counter CRUD
    Route::post('/counters', [CounterController::class, 'store']);
    Route::get('/counters/{id}', [CounterController::class, 'show']);
    Route::put('/counters/{id}', [CounterController::class, 'update']);
    Route::patch('/counters/{id}', [CounterController::class, 'update']);
    Route::delete('/counters/{id}', [CounterController::class, 'destroy']);

    // User CRUD
    Route::put('/users/{id}', [AuthController::class, 'updateUser']);
    Route::delete('/users/{id}', [AuthController::class, 'deleteUser']);
    Route::get('/users', [AuthController::class, 'listUsers']);

    // Service CRUD
    Route::post('/services', [ServiceController::class, 'store']);
    Route::put('/services/{id}', [ServiceController::class, 'update']);
    Route::patch('/services/{id}', [ServiceController::class, 'update']);
    Route::delete('/services/{id}', [ServiceController::class, 'destroy']);
});
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CounterController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\QueueController;


Route::post('/register', [App\Http\Controllers\AuthController::class, 'register']);
Route::post('/login', [App\Http\Controllers\AuthController::class, 'login']);

// Public Routes (Accessible by Kiosk)
Route::get('/branches', [BranchController::class, 'index']);
Route::get('/services', [ServiceController::class, 'index']); 
Route::get('/branches/{id}', [BranchController::class, 'show']); // also useful for public
Route::get('/services/{id}', [ServiceController::class, 'show']); // also useful for public
Route::get('/tickets/{id}', [TicketController::class, 'show']);
Route::post('/tickets', [TicketController::class, 'store']);

Route::middleware(['auth:sanctum', 'role:staff'])->group(function () {
    Route::post('/counter/call-next', [TicketController::class, 'callNext']); // call ticket
    Route::post('/tickets/{ticket}/serve', [TicketController::class, 'serve']); // serve ticket
    Route::post('/tickets/{ticket}/skip', [TicketController::class, 'skip']);   // skip ticket
    Route::get('/tickets', [TicketController::class, 'index']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    
    // Branch CRUD (Protected)
    Route::post('/branches', [BranchController::class, 'store']);
    Route::put('/branches/{id}', [BranchController::class, 'update']);
    Route::patch('/branches/{id}', [BranchController::class, 'update']);
    Route::delete('/branches/{id}', [BranchController::class, 'destroy']);

    // Counter CRUD
    Route::get('/counters', [CounterController::class, 'index']);
    Route::post('/counters', [CounterController::class, 'store']);
    Route::get('/counters/{id}', [CounterController::class, 'show']);
    Route::put('/counters/{id}', [CounterController::class, 'update']);
    Route::patch('/counters/{id}', [CounterController::class, 'update']);
    Route::delete('/counters/{id}', [CounterController::class, 'destroy']);

    // Service CRUD (Protected)
    Route::post('/services', [ServiceController::class, 'store']);
    Route::put('/services/{id}', [ServiceController::class, 'update']);
    Route::patch('/services/{id}', [ServiceController::class, 'update']);
    Route::delete('/services/{id}', [ServiceController::class, 'destroy']);
    
    // Ticket CRUD
    Route::post('/counter/call-next', [TicketController::class, 'callNext']); // call ticket
    Route::post('/tickets/{ticket}/serve', [TicketController::class, 'serve']); // serve ticket
    Route::post('/tickets/{ticket}/skip', [TicketController::class, 'skip']);   // skip ticket
    Route::get('/tickets', [TicketController::class, 'index']);
    //auth
    Route::get('/me',          [AuthController::class, 'me']);
    Route::post('/logout',     [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    Route::post('/refresh',    [AuthController::class, 'refresh']);
});
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\IdolController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\SongController;
use App\Http\Controllers\PromotionController;

$authMiddleware = env('SESSION_ONLY_AUTH', false) ? [] : ['auth:sanctum'];

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| KPOP IDOL MANAGER 2025 - API v1
*/

// Public auth routes
Route::prefix('v1')->group(function () {
    // Auth
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    
    // Protected routes
    Route::middleware($authMiddleware)->group(function () {
        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        
        // Player
        Route::get('/player', [PlayerController::class, 'show']);
        Route::put('/player', [PlayerController::class, 'update']);
        
        // Managers
        Route::get('/managers', [ManagerController::class, 'index']);
        Route::post('/managers/select', [ManagerController::class, 'select']);
        
        // Idols
        Route::get('/idols', [IdolController::class, 'index']);
        Route::post('/idols/scout', [IdolController::class, 'scout']);
        Route::post('/idols/{idol}/train', [IdolController::class, 'train']);
        Route::delete('/idols/{idol}', [IdolController::class, 'release']);
        
        // Groups
        Route::get('/groups', [GroupController::class, 'index']);
        Route::post('/groups', [GroupController::class, 'store']);
        Route::get('/groups/{group}', [GroupController::class, 'show']);
        Route::put('/groups/{group}', [GroupController::class, 'update']);
        Route::post('/groups/{group}/members', [GroupController::class, 'addMember']);
        Route::delete('/groups/{group}/members/{idol}', [GroupController::class, 'removeMember']);
        
        // Songs
        Route::get('/songs', [SongController::class, 'index']);
        Route::post('/songs', [SongController::class, 'store']);
        Route::get('/songs/{song}', [SongController::class, 'show']);
        
        // Promotions
        Route::get('/promotions', [PromotionController::class, 'index']);
        Route::get('/promotions/available', [PromotionController::class, 'available']);
        Route::post('/promotions', [PromotionController::class, 'start']);
        Route::post('/promotions/{promotion}/complete', [PromotionController::class, 'complete']);
    });
});


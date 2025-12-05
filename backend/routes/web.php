<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| In production, Laravel serves the Vue SPA from the public folder.
| All non-API routes fall through to index.html for Vue Router to handle.
*/

// Serve Vue SPA - catch all routes
Route::get('/{any}', function () {
    $path = public_path('index.html');
    
    if (File::exists($path)) {
        return File::get($path);
    }
    
    // Fallback for development
    return view('welcome');
})->where('any', '^(?!api|sanctum).*$');

<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| WEBIS is an API-only Laravel application; the user interface is the React
| SPA in ../frontend. The only web routes are the Sanctum CSRF cookie
| endpoint (registered by Sanctum itself) and this landing stub, which exists
| so hitting the API host directly gives a useful answer instead of a 404.
|
*/

Route::get('/', function () {
    return response()->json([
        'success' => true,
        'data' => [
            'application' => config('app.name'),
            'description' => 'WEBIS API. The user interface runs separately.',
            'frontend' => config('webis.frontend_url'),
            'health' => url('/api/health'),
        ],
    ]);
})->name('home');

/*
 * Named 'login' so any framework code that redirects guests (for example a
 * signed email-verification link opened while signed out) resolves instead of
 * throwing a RouteNotFoundException. It hands the visitor to the SPA.
 */
Route::get('/login', function () {
    return redirect()->away(config('webis.frontend_url').'/login');
})->name('login');

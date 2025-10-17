<?php

use App\Http\Controllers\WeatherController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// ¡AÑADE TU RUTA DE CLIMA AQUÍ! (Es una ruta pública)
Route::get('/weather', [WeatherController::class, 'getWeather']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

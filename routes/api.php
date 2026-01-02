<?php

use App\Http\Controllers\Api\ExchangeRateController;
use App\Http\Controllers\Api\ExpensesByCategoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// These routes are also defined in web.php for easier access
// Use /api prefix for JSON responses

Route::get('exchange-rates', [ExchangeRateController::class, 'index']);
Route::post('exchange-rates/refresh', [ExchangeRateController::class, 'refresh']);
Route::get('expenses-by-category', ExpensesByCategoryController::class);

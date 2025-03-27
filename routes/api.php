<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\TransactionController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
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

RateLimiter::for('api', function ($request) {
    return Limit::perMinute(30)->by(optional($request->user())->id ?: $request->ip());
});
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/accounts', [AccountController::class, 'createAccount']);
    Route::get('/accounts/{account_number}', [AccountController::class, 'showAccountDetails']);
    Route::put('/accounts/{account_number}', [AccountController::class, 'updateAccountDetails']);
    Route::delete('/accounts/{account_number}', [AccountController::class, 'deactivateAccount']);


    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);

    Route::post('/transfer', [TransactionController::class, 'transfer']);
    Route::get('/transactions/pdf', [TransactionController::class, 'downloadStatement']);
});

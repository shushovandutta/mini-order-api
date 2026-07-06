<?php

use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\OrderController;
use App\Http\Controllers\Api\v1\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

//Test Redis Connection
Route::get('/test-redis', function () {
    dump(config('cache.default'));
    dump(Cache::getDefaultDriver());
    Cache::put('test_key', 'Hello Raj, Redis is working!', 60);
    return response()->json(['message' => 'Data written to Redis']);
});

// v1 Prefix Group
Route::prefix('v1')->middleware('throttle:global_api_rate_limiter')->group(function () {


    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        //Route for Product
        Route::apiResource('products', ProductController::class);


        Route::post('/orders', [OrderController::class, 'store']); // Create new Order
        Route::get('/orders', [OrderController::class, 'index']); // Order List 
        Route::get('/orders/{id}', [OrderController::class, 'show']); // Single Order Details
    });
}); // End of Prefix Group
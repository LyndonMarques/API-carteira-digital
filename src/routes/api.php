<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\TransactionController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/teste', function () {
    return response()->json(['status' => 'OK']);
});

Route::middleware('throttle:transfers')->post('/transfer', [TransactionController::class, 'transfer']);
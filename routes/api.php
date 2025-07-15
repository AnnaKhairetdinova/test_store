<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/catalogs', [ProductController::class, 'index']);
Route::post('/create-order', [OrderController::class, 'store']);
Route::post('/approve-order', [OrderController::class, 'approve']);

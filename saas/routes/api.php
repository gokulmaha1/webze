<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ─── Analytics (Admin Dashboard) ──────────────────────────────────────────
Route::get('/dashboard/stats',    [\App\Http\Controllers\DashboardApiController::class, 'stats']);
Route::get('/analytics/websites', [\App\Http\Controllers\DashboardApiController::class, 'websites']);
Route::get('/analytics/revenue',  [\App\Http\Controllers\DashboardApiController::class, 'revenue']);
Route::get('/analytics/templates',[\App\Http\Controllers\DashboardApiController::class, 'templates']);
Route::get('/analytics/users',    [\App\Http\Controllers\DashboardApiController::class, 'users']);

// ─── Landing Page: Scrape social profile → Generate website ───────────────
Route::post('/scrape-and-generate', [\App\Http\Controllers\ScrapeSiteController::class, 'handle']);

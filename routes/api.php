<?php

use Illuminate\Support\Facades\Route;
use Articlai\Articlai\Http\Controllers\ArticlaiController;

/*
|--------------------------------------------------------------------------
| ArticlAI API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the ArticlaiServiceProvider and are
| automatically prefixed with the configured API prefix and assigned
| the configured middleware group.
|
*/

// Validation endpoint (optional but recommended)
Route::get('/validate', [ArticlaiController::class, 'validate'])
    ->name('articlai.validate');

// Content management endpoints
Route::apiResource('posts', ArticlaiController::class, [
    'parameters' => ['posts' => 'id']
])->names([
    'index' => 'articlai.posts.index',
    'store' => 'articlai.posts.store',
    'show' => 'articlai.posts.show',
    'update' => 'articlai.posts.update',
    'destroy' => 'articlai.posts.destroy',
]);
// Alternative explicit route definitions (if you prefer explicit over resource routes)
/*
Route::get('/posts', [ArticlaiController::class, 'index'])->name('articlai.posts.index');
Route::post('/posts', [ArticlaiController::class, 'store'])->name('articlai.posts.store');
Route::get('/posts/{id}', [ArticlaiController::class, 'show'])->name('articlai.posts.show');
Route::put('/posts/{id}', [ArticlaiController::class, 'update'])->name('articlai.posts.update');
Route::delete('/posts/{id}', [ArticlaiController::class, 'destroy'])->name('articlai.posts.destroy');
*/

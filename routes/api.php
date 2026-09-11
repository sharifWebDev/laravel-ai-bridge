<?php

use Illuminate\Support\Facades\Route;
use Sharifuddin\LaravelAiBridge\Http\Controllers\AiController;

Route::post('/prompt', [AiController::class, 'handle'])->name('ai-bridge.prompt');

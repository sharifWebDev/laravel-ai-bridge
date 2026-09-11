<?php

use Illuminate\Support\Facades\Route;
use Sharifuddin\LaravelAiBridge\Http\Controllers\AiController;

Route::get('/assistant', [AiController::class, 'index'])->name('ai-bridge.assistant');

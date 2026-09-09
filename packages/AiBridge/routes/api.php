<?php

use Illuminate\Support\Facades\Route;
use Sharifuddin\LaravelAiBridge\Http\Controllers\AiController;

Route::post('/ai/prompt', [AiController::class, 'handle']);

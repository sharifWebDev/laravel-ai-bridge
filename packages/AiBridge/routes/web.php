<?php

use Illuminate\Support\Facades\Route;
use Sharifuddin\LaravelAiBridge\Http\Controllers\AiController;

Route::get('/ai/assistant', [AiController::class, 'index']); // ওয়েব ইন্টারফেস বা চ্যাট ভিউ দেখানোর জন্য

<?php

use Illuminate\Support\Facades\Route;
use RunConvert\Laravel\Http\WebhookController;

Route::post(config('runconvert.webhook.path'), WebhookController::class)
    ->middleware(config('runconvert.webhook.middleware', []))
    ->name('runconvert.webhook');

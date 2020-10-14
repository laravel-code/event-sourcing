<?php

use Illuminate\Support\Facades\Route;

Route::post('/webhook', [\LaravelCode\EventSourcing\Controllers\WebhookController::class, 'handle']);

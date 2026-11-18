<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use JiordiViera\LaravelLogCleaner\Dashboard\Http\Controllers\OverviewController;

Route::get('/', OverviewController::class)->name('overview');

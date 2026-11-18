<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

it('registers no dashboard route by default', function () {
    expect(Route::has('log-cleaner.overview'))->toBeFalse();

    $this->get('/log-cleaner')->assertNotFound();
});

it('defines the dashboard gates for the local environment only', function () {
    expect(Gate::has('viewLogCleaner'))->toBeTrue()
        ->and(Gate::has('manageLogCleaner'))->toBeTrue()
        ->and(Gate::check('viewLogCleaner', [null]))->toBeFalse();
});

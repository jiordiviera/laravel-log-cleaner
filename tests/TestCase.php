<?php

namespace JiordiViera\LaravelLogCleaner\Tests;
use JiordiViera\LaravelLogCleaner\LaravelLogCleanerServiceProvider;
use \Orchestra\Testbench\TestCase as OrchestraTestCase;
class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return[
            LaravelLogCleanerServiceProvider::class
        ];
    }

}
<?php

declare(strict_types=1);

namespace GraystackIT\DymoPrinter\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Test case that loads the package's database migrations and refreshes the database between tests.
 */
class DatabaseTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * Load the package migrations before each test.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}

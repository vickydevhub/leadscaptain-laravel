<?php

declare(strict_types=1);

namespace Leadscaptain\LaravelLeadscaptain\Tests\Unit\Config;

use Leadscaptain\LaravelLeadscaptain\LeadscaptainServiceProvider;
use Orchestra\Testbench\TestCase;

final class LeadscaptainConfigTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            LeadscaptainServiceProvider::class,
        ];
    }

    public function test_default_concurrency_is_ten(): void
    {
        self::assertSame(
            10,
            config('leadscaptain.concurrency'),
        );
    }
}

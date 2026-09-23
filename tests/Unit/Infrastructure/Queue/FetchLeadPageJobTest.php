<?php

declare(strict_types=1);

namespace Leadscaptain\LaravelLeadscaptain\Tests\Unit\Infrastructure\Queue;

use Leadscaptain\LaravelLeadscaptain\Infrastructure\Queue\FetchLeadPageJob;
use Orchestra\Testbench\TestCase;

final class FetchLeadPageJobTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set(
            'leadscaptain.queue.connection',
            'redis',
        );

        $app['config']->set(
            'leadscaptain.queue.queue',
            'leadscaptain',
        );
    }

    public function test_it_uses_the_leadscaptain_queue(): void
    {
        $job = new FetchLeadPageJob(
            page: 2,
            perPage: 100,
        );

        self::assertSame(
            'redis',
            $job->connection,
        );

        self::assertSame(
            'leadscaptain',
            $job->queue,
        );
    }
}

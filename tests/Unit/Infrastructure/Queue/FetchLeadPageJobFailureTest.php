<?php

declare(strict_types=1);

namespace Leadscaptain\LaravelLeadscaptain\Tests\Unit\Infrastructure\Queue;

use Illuminate\Support\Facades\Event;
use Leadscaptain\LaravelLeadscaptain\Domain\Events\LeadImportFailed;
use Leadscaptain\LaravelLeadscaptain\Infrastructure\Queue\FetchLeadPageJob;
use Orchestra\Testbench\TestCase;
use RuntimeException;

final class FetchLeadPageJobFailureTest extends TestCase
{
    public function test_it_dispatches_failure_event(): void
    {
        Event::fake();

        $exception = new RuntimeException(
            'API request failed.',
        );

        $job = new FetchLeadPageJob(
            page: 5,
            perPage: 100,
        );

        $job->failed($exception);

        Event::assertDispatched(
            LeadImportFailed::class,
            function (LeadImportFailed $event) use ($exception): bool {
                return $event->page === 5
                    && $event->exception === $exception;
            },
        );
    }
}

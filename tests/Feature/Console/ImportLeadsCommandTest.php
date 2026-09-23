<?php

declare(strict_types=1);

namespace Leadscaptain\LaravelLeadscaptain\Tests\Feature\Console;

use Illuminate\Support\Facades\Bus;
use Leadscaptain\LaravelLeadscaptain\Infrastructure\Queue\FetchLeadsJob;
use Leadscaptain\LaravelLeadscaptain\LeadscaptainServiceProvider;
use Orchestra\Testbench\TestCase;

final class ImportLeadsCommandTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            LeadscaptainServiceProvider::class,
        ];
    }

    public function test_it_dispatches_the_import_job(): void
    {
        Bus::fake();

        $this->artisan('leadscaptain:import')
            ->expectsOutput(
                'Leadscaptain lead import has been queued.'
            )
            ->assertExitCode(0);

        Bus::assertDispatched(
            FetchLeadsJob::class,
        );
    }
}

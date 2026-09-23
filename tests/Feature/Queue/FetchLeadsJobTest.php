<?php

declare(strict_types=1);

namespace Leadscaptain\LaravelLeadscaptain\Tests\Feature\Queue;

use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Leadscaptain\LaravelLeadscaptain\Application\Services\ImportLeads;
use Leadscaptain\LaravelLeadscaptain\Infrastructure\Http\LeadscaptainClient;
use Leadscaptain\LaravelLeadscaptain\Infrastructure\Queue\FetchLeadPageJob;
use Leadscaptain\LaravelLeadscaptain\Infrastructure\Queue\FetchLeadsJob;
use Leadscaptain\LaravelLeadscaptain\LeadscaptainServiceProvider;
use Orchestra\Testbench\TestCase;

final class FetchLeadsJobTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            LeadscaptainServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set(
            'leadscaptain.base_url',
            'https://api.leadscaptain.com',
        );

        $app['config']->set(
            'leadscaptain.api_key',
            'test-key',
        );

        $app['config']->set(
            'leadscaptain.per_page',
            100,
        );

        $app['config']->set(
            'leadscaptain.max_page',
            1000,
        );

        $app['config']->set(
            'leadscaptain.retry_backoff',
            [0, 0, 0],
        );
    }

    public function test_it_fetches_page_one_and_batches_remaining_pages(): void
    {
        Bus::fake();

        Http::fake([
            'https://api.leadscaptain.com/leads*' => Http::response([
                'data' => [
                    [
                        'id' => 'lead-1',
                        'first_name' => 'John',
                        'last_name' => 'Doe',
                        'email' => 'john@example.com',
                    ],
                ],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 4,
                ],
            ]),
        ]);

        $job = new FetchLeadsJob;

        $job->handle(
            app(LeadscaptainClient::class),
            app(ImportLeads::class),
        );

        $this->assertDatabaseHas('leads', [
            'external_id' => 'lead-1',
        ]);

        Bus::assertBatched(
            function (PendingBatch $batch): bool {
                if ($batch->jobs->count() !== 3) {
                    return false;
                }

                foreach ($batch->jobs as $job) {
                    if (! $job instanceof FetchLeadPageJob) {
                        return false;
                    }
                }

                return true;
            },
        );
    }
}

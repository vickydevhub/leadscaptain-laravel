<?php

declare(strict_types=1);

namespace Leadscaptain\LaravelLeadscaptain\Tests\Feature\Queue;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Leadscaptain\LaravelLeadscaptain\Application\Services\ImportLeads;
use Leadscaptain\LaravelLeadscaptain\Infrastructure\Http\LeadscaptainClient;
use Leadscaptain\LaravelLeadscaptain\Infrastructure\Queue\FetchLeadPageJob;
use Leadscaptain\LaravelLeadscaptain\LeadscaptainServiceProvider;
use Orchestra\Testbench\TestCase;

final class FetchLeadPageJobTest extends TestCase
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
            'leadscaptain.retry_backoff',
            [0, 0, 0],
        );
    }

    public function test_it_fetches_and_stores_a_page(): void
    {
        Http::fake([
            'https://api.leadscaptain.com/leads*' => Http::response([
                'data' => [
                    [
                        'id' => 'lead-100',
                        'first_name' => 'John',
                        'last_name' => 'Doe',
                        'email' => 'john@example.com',
                    ],
                ],
                'meta' => [
                    'current_page' => 2,
                    'last_page' => 3,
                ],
            ]),
        ]);

        $job = new FetchLeadPageJob(
            page: 2,
            perPage: 100,
        );

        $job->handle(
            app(
                LeadscaptainClient::class
            ),
            app(
                ImportLeads::class
            ),
        );

        $this->assertDatabaseHas('leads', [
            'external_id' => 'lead-100',
            'email' => 'john@example.com',
        ]);

        Http::assertSent(
            fn ($request): bool => str_contains(
                $request->url(),
                'page=2',
            ),
        );
    }
}

<?php

declare(strict_types=1);

namespace Leadscaptain\LaravelLeadscaptain\Tests\Unit\Infrastructure\Http;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Leadscaptain\LaravelLeadscaptain\Infrastructure\Http\LeadscaptainClient;
use Orchestra\Testbench\TestCase;

final class LeadscaptainClientTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set(
            'leadscaptain.base_url',
            'https://api.leadscaptain.com',
        );

        $app['config']->set(
            'leadscaptain.api_key',
            'test-api-key',
        );

        $app['config']->set(
            'leadscaptain.timeout',
            30,
        );

        $app['config']->set(
            'leadscaptain.retry_times',
            3,
        );

        $app['config']->set(
            'leadscaptain.retry_backoff',
            [1, 5, 30],
        );
    }

    public function test_it_fetches_a_page_of_leads(): void
    {
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
                    'last_page' => 3,
                    'per_page' => 100,
                    'total' => 250,
                ],
            ], 200),
        ]);

        $client = app(LeadscaptainClient::class);

        $result = $client->fetchPage(1, 100);

        self::assertSame(
            'lead-1',
            $result['data'][0]['id'],
        );

        self::assertSame(
            3,
            $result['meta']['last_page'],
        );

        Http::assertSent(function ($request): bool {
            return $request->url() ===
                'https://api.leadscaptain.com/leads?page=1&per_page=100'
                && $request->header('X-API-Key')[0] === 'test-api-key';
        });
    }

    public function test_it_retries_on_rate_limit(): void
    {
        Http::fakeSequence()
            ->push([], 429)
            ->push([], 429)
            ->push([
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                ],
            ], 200);

        $client = app(LeadscaptainClient::class);

        $result = $client->fetchPage(1, 100);

        self::assertSame([], $result['data']);

        Http::assertSentCount(3);
    }

    public function test_it_retries_on_server_error(): void
    {
        Http::fakeSequence()
            ->push([], 500)
            ->push([], 503)
            ->push([
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                ],
            ], 200);

        $client = app(LeadscaptainClient::class);

        $result = $client->fetchPage(1, 100);

        self::assertSame([], $result['data']);

        Http::assertSentCount(3);
    }

    public function test_it_throws_when_api_still_fails_after_retries(): void
    {
        Http::fake([
            'https://api.leadscaptain.com/leads*' => Http::response([], 500),
        ]);

        $client = app(LeadscaptainClient::class);

        $this->expectException(RequestException::class);

        $client->fetchPage(1, 100);
    }

    public function test_it_rejects_an_invalid_api_response(): void
    {
        Http::fake([
            'https://api.leadscaptain.com/leads*' => Http::response([
                'unexpected' => 'response',
            ], 200),
        ]);

        $client = app(LeadscaptainClient::class);

        $this->expectException(\RuntimeException::class);

        $client->fetchPage(1, 100);
    }
}

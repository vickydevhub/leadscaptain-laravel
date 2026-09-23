<?php

declare(strict_types=1);

namespace Leadscaptain\LaravelLeadscaptain\Infrastructure\Http;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class LeadscaptainClient
{
    public function fetchPage(
        int $page,
        int $perPage,
    ): array {
        $apiKey = config('leadscaptain.api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException(
                'LEADSCAPTAIN_API_KEY is not configured.',
            );
        }

        $startedAt = microtime(true);

        $response = Http::baseUrl(
            (string) config('leadscaptain.base_url'),
        )
            ->withHeaders([
                'X-API-Key' => $apiKey,
                'Accept' => 'application/json',
            ])
            ->timeout(
                (int) config('leadscaptain.timeout')
            )
            ->retry(
                (int) config('leadscaptain.retry_times'),
                $this->retryDelay(...),
                function (
                    Throwable $exception,
                    PendingRequest $request,
                    ?string $response,
                ): bool {
                    if ($exception instanceof ConnectionException) {
                        return true;
                    }

                    if ($exception instanceof RequestException) {
                        $status = $exception->response->status();

                        return $status === 429
                            || $status >= 500;
                    }

                    return false;
                },
            )
            ->get('/leads', [
                'page' => $page,
                'per_page' => $perPage,
            ]);

        Log::channel('leadscaptain')->info(
            'Leadscaptain API request completed.',
            [
                'page' => $page,
                'status' => $response->status(),
                'duration_ms' => (int) (
                    (microtime(true) - $startedAt) * 1000
                ),
            ],
        );

        if ($response->failed()) {
            throw new RuntimeException(
                sprintf(
                    'Leadscaptain API request failed with status %d.',
                    $response->status(),
                ),
            );
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException(
                'Leadscaptain API returned an invalid response.',
            );
        }

        $this->validateResponse($payload);

        return $payload;
    }

    private function retryDelay(int $attempt): int
    {
        $backoff = config(
            'leadscaptain.retry_backoff',
            [1000, 5000, 30000],
        );

        return (int) (
            $backoff[$attempt - 1]
            ?? end($backoff)
        );
    }

    private function validateResponse(array $payload): void
    {
        if (
            ! array_key_exists('data', $payload)
            || ! array_key_exists('meta', $payload)
        ) {
            throw new RuntimeException(
                'Leadscaptain API response is missing data or meta.',
            );
        }

        if (! is_array($payload['data'])) {
            throw new RuntimeException(
                'Leadscaptain API data must be an array.',
            );
        }

        if (! is_array($payload['meta'])) {
            throw new RuntimeException(
                'Leadscaptain API meta must be an array.',
            );
        }
    }
}

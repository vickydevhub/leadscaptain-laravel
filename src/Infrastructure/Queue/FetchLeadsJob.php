<?php

declare(strict_types=1);

namespace Leadscaptain\LaravelLeadscaptain\Infrastructure\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Leadscaptain\LaravelLeadscaptain\Application\Services\ImportLeads;
use Leadscaptain\LaravelLeadscaptain\Infrastructure\Http\LeadscaptainClient;
use RuntimeException;

final class FetchLeadsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct()
    {
        $this->onConnection(
            (string) config(
                'leadscaptain.queue.connection',
                'redis',
            ),
        );

        $this->onQueue(
            (string) config(
                'leadscaptain.queue.queue',
                'leadscaptain',
            ),
        );
    }

    public function handle(
        LeadscaptainClient $client,
        ImportLeads $importLeads,
    ): void {
        $perPage = (int) config(
            'leadscaptain.per_page',
            100,
        );

        $response = $client->fetchPage(
            1,
            $perPage,
        );

        $importLeads->import(
            $response['data'],
        );

        $lastPage = $this->getLastPage(
            $response,
        );

        if ($lastPage <= 1) {
            return;
        }

        $jobs = [];

        for ($page = 2; $page <= $lastPage; $page++) {
            $jobs[] = new FetchLeadPageJob(
                page: $page,
                perPage: $perPage,
            );
        }

        Bus::batch($jobs)
            ->name('Leadscaptain lead import')
            ->dispatch();
    }

    private function getLastPage(array $response): int
    {
        $lastPage = (int) (
            $response['meta']['last_page'] ?? 0
        );

        if ($lastPage < 1) {
            throw new RuntimeException(
                'Leadscaptain response does not contain a valid last_page.',
            );
        }

        $maxPage = (int) config(
            'leadscaptain.max_page',
            1000,
        );

        return min(
            $lastPage,
            $maxPage,
        );
    }
}

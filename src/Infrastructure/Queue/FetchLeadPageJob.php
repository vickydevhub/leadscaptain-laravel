<?php

declare(strict_types=1);

namespace Leadscaptain\LaravelLeadscaptain\Infrastructure\Queue;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Leadscaptain\LaravelLeadscaptain\Application\Services\ImportLeads;
use Leadscaptain\LaravelLeadscaptain\Domain\Events\LeadImportFailed;
use Leadscaptain\LaravelLeadscaptain\Infrastructure\Http\LeadscaptainClient;
use Throwable;

final class FetchLeadPageJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [
        1,
        5,
        30,
    ];

    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
    ) {
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
        if ($this->batch()?->cancelled()) {
            return;
        }

        $response = $client->fetchPage(
            $this->page,
            $this->perPage,
        );

        $importLeads->import(
            $response['data'],
        );
    }

    public function failed(Throwable $exception): void
    {
        Log::channel('leadscaptain')->error(
            'Leadscaptain page import permanently failed.',
            [
                'page' => $this->page,
                'message' => $exception->getMessage(),
            ],
        );

        Event::dispatch(
            new LeadImportFailed(
                page: $this->page,
                exception: $exception,
            ),
        );
    }
}

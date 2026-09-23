<?php

declare(strict_types=1);

namespace Leadscaptain\LaravelLeadscaptain\Console\Commands;

use Illuminate\Console\Command;
use Leadscaptain\LaravelLeadscaptain\Infrastructure\Queue\FetchLeadsJob;

final class ImportLeadsCommand extends Command
{
    protected $signature = 'leadscaptain:import';

    protected $description = 'Import leads from Leadscaptain';

    public function handle(): int
    {
        FetchLeadsJob::dispatch();

        $this->info(
            'Leadscaptain lead import has been queued.'
        );

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace Leadscaptain\LaravelLeadscaptain\Application\Services;

use Leadscaptain\LaravelLeadscaptain\Application\DTOs\LeadData;
use Leadscaptain\LaravelLeadscaptain\Domain\Repositories\LeadRepository;

final readonly class ImportLeads
{
    public function __construct(
        private LeadRepository $repository,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $leads
     */
    public function import(array $leads): int
    {
        $imported = 0;

        foreach ($leads as $lead) {
            $data = LeadData::fromArray($lead);

            if ($data->id === '') {
                continue;
            }

            $this->repository->upsert(
                $data->toDomain(),
            );

            $imported++;
        }

        return $imported;
    }
}

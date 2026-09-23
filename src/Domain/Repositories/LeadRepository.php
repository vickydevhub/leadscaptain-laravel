<?php

declare(strict_types=1);

namespace Leadscaptain\LaravelLeadscaptain\Domain\Repositories;

use Leadscaptain\LaravelLeadscaptain\Domain\Entities\Lead;

interface LeadRepository
{
    public function upsert(Lead $lead): void;
}

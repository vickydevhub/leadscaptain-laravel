<?php

declare(strict_types=1);

namespace Leadscaptain\LaravelLeadscaptain\Domain\Events;

use Leadscaptain\LaravelLeadscaptain\Domain\Entities\Lead;

final readonly class LeadImported
{
    public function __construct(
        public Lead $lead,
    ) {}
}

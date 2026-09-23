<?php

declare(strict_types=1);

namespace Leadscaptain\LaravelLeadscaptain\Domain\Events;

use Throwable;

final readonly class LeadImportFailed
{
    public function __construct(
        public int $page,
        public Throwable $exception,
    ) {}
}

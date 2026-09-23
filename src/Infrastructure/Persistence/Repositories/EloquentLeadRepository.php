<?php

declare(strict_types=1);

namespace Leadscaptain\LaravelLeadscaptain\Infrastructure\Persistence\Repositories;

use Leadscaptain\LaravelLeadscaptain\Domain\Entities\Lead;
use Leadscaptain\LaravelLeadscaptain\Domain\Repositories\LeadRepository;
use Leadscaptain\LaravelLeadscaptain\Infrastructure\Persistence\Models\LeadModel;

final class EloquentLeadRepository implements LeadRepository
{
    public function upsert(Lead $lead): void
    {
        LeadModel::query()->updateOrCreate(
            [
                'external_id' => $lead->id,
            ],
            [
                'first_name' => $lead->firstName,
                'last_name' => $lead->lastName,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'attributes' => $lead->attributes,
            ],
        );
    }
}

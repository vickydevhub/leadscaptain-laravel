<?php

declare(strict_types=1);

namespace Leadscaptain\LaravelLeadscaptain\Tests\Unit\Domain;

use Leadscaptain\LaravelLeadscaptain\Domain\Entities\Lead;
use Leadscaptain\LaravelLeadscaptain\Domain\Events\LeadImported;
use PHPUnit\Framework\TestCase;

final class LeadTest extends TestCase
{
    public function test_lead_contains_the_required_information(): void
    {
        $lead = new Lead(
            id: 'lead-123',
            firstName: 'John',
            lastName: 'Doe',
            email: 'john@example.com',
            phone: '+31612345678',
            attributes: [
                'source' => 'website',
            ],
        );

        self::assertSame('lead-123', $lead->id);
        self::assertSame('John', $lead->firstName);
        self::assertSame('Doe', $lead->lastName);
        self::assertSame('john@example.com', $lead->email);
        self::assertSame('+31612345678', $lead->phone);
        self::assertSame(
            ['source' => 'website'],
            $lead->attributes,
        );
    }

    public function test_lead_imported_event_contains_the_lead(): void
    {
        $lead = new Lead(
            id: 'lead-123',
            firstName: 'John',
            lastName: 'Doe',
            email: 'john@example.com',
            phone: null,
        );

        $event = new LeadImported($lead);

        self::assertSame($lead, $event->lead);
    }
}

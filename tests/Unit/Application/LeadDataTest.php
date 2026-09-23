<?php

declare(strict_types=1);

namespace Leadscaptain\LaravelLeadscaptain\Tests\Unit\Application;

use Leadscaptain\LaravelLeadscaptain\Application\DTOs\LeadData;
use PHPUnit\Framework\TestCase;

final class LeadDataTest extends TestCase
{
    public function test_api_data_is_mapped_to_lead_data(): void
    {
        $data = LeadData::fromArray([
            'id' => 'lead-123',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+31612345678',
        ]);

        self::assertSame('lead-123', $data->id);
        self::assertSame('John', $data->firstName);
        self::assertSame('Doe', $data->lastName);
        self::assertSame('john@example.com', $data->email);
        self::assertSame('+31612345678', $data->phone);
    }

    public function test_api_data_can_be_converted_to_domain_lead(): void
    {
        $data = LeadData::fromArray([
            'id' => 'lead-123',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+31612345678',
        ]);

        $lead = $data->toDomain();

        self::assertSame('lead-123', $lead->id);
        self::assertSame('John', $lead->firstName);
        self::assertSame('Doe', $lead->lastName);
        self::assertSame('john@example.com', $lead->email);
        self::assertSame('+31612345678', $lead->phone);
    }
}

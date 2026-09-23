<?php

declare(strict_types=1);

namespace Leadscaptain\LaravelLeadscaptain\Tests\Unit\Application;

use Leadscaptain\LaravelLeadscaptain\Application\Services\ImportLeads;
use Leadscaptain\LaravelLeadscaptain\Domain\Entities\Lead;
use Leadscaptain\LaravelLeadscaptain\Domain\Repositories\LeadRepository;
use PHPUnit\Framework\TestCase;

final class ImportLeadsTest extends TestCase
{
    public function test_it_imports_leads_through_the_repository(): void
    {
        $repository = new InMemoryLeadRepository;

        $service = new ImportLeads($repository);

        $count = $service->import([
            [
                'id' => 'lead-1',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
                'phone' => null,
            ],
            [
                'id' => 'lead-2',
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'email' => 'jane@example.com',
                'phone' => null,
            ],
        ]);

        self::assertSame(2, $count);
        self::assertCount(2, $repository->leads);
        self::assertSame(
            'lead-1',
            $repository->leads[0]->id,
        );
    }

    public function test_it_skips_records_without_an_id(): void
    {
        $repository = new InMemoryLeadRepository;

        $service = new ImportLeads($repository);

        $count = $service->import([
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
            ],
        ]);

        self::assertSame(0, $count);
        self::assertCount(0, $repository->leads);
    }
}

final class InMemoryLeadRepository implements LeadRepository
{
    /**
     * @var list<Lead>
     */
    public array $leads = [];

    public function upsert(Lead $lead): void
    {
        $this->leads[] = $lead;
    }
}

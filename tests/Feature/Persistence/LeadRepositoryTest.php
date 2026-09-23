<?php

declare(strict_types=1);

namespace Leadscaptain\LaravelLeadscaptain\Tests\Feature\Persistence;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Leadscaptain\LaravelLeadscaptain\Domain\Entities\Lead;
use Leadscaptain\LaravelLeadscaptain\Domain\Repositories\LeadRepository;
use Leadscaptain\LaravelLeadscaptain\LeadscaptainServiceProvider;
use Orchestra\Testbench\TestCase;

final class LeadRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            LeadscaptainServiceProvider::class,
        ];
    }

    public function test_it_stores_a_lead(): void
    {
        $repository = app(LeadRepository::class);

        $repository->upsert(
            new Lead(
                id: 'lead-123',
                firstName: 'John',
                lastName: 'Doe',
                email: 'john@example.com',
                phone: '123456789',
                attributes: [
                    'source' => 'leadscaptain',
                ],
            ),
        );

        $this->assertDatabaseHas('leads', [
            'external_id' => 'lead-123',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_it_updates_existing_lead_instead_of_creating_duplicate(): void
    {
        $repository = app(LeadRepository::class);

        $repository->upsert(
            new Lead(
                id: 'lead-123',
                firstName: 'John',
                lastName: 'Doe',
                email: 'john@example.com',
                phone: null,
            ),
        );

        $repository->upsert(
            new Lead(
                id: 'lead-123',
                firstName: 'Jane',
                lastName: 'Doe',
                email: 'jane@example.com',
                phone: null,
            ),
        );

        $this->assertDatabaseCount('leads', 1);

        $this->assertDatabaseHas('leads', [
            'external_id' => 'lead-123',
            'first_name' => 'Jane',
            'email' => 'jane@example.com',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Leadscaptain\LaravelLeadscaptain\Application\DTOs;

use Leadscaptain\LaravelLeadscaptain\Domain\Entities\Lead;

final readonly class LeadData
{
    public function __construct(
        public string $id,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $email,
        public ?string $phone,
        public array $attributes = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            firstName: self::nullableString($data['first_name'] ?? null),
            lastName: self::nullableString($data['last_name'] ?? null),
            email: self::nullableString($data['email'] ?? null),
            phone: self::nullableString($data['phone'] ?? null),
            attributes: $data,
        );
    }

    public function toDomain(): Lead
    {
        return new Lead(
            id: $this->id,
            firstName: $this->firstName,
            lastName: $this->lastName,
            email: $this->email,
            phone: $this->phone,
            attributes: $this->attributes,
        );
    }

    private static function nullableString(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }
}

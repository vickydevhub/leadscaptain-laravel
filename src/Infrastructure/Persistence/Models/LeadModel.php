<?php

declare(strict_types=1);

namespace Leadscaptain\LaravelLeadscaptain\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class LeadModel extends Model
{
    protected $table = 'leads';

    protected $fillable = [
        'external_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'attributes',
    ];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
        ];
    }
}

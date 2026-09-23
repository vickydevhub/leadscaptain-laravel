# Leadscaptain Laravel Package

A reusable Laravel package for importing leads from the Leadscaptain API into a Laravel application.

The package is designed for Laravel 12+, PHP 8.4+, Redis queues, and scalable background processing.

> This repository contains the reusable package itself. It is **not** a standalone Laravel application.

## Requirements

- PHP 8.4+
- Laravel 12+
- Composer 2+
- Redis for queued processing
- MySQL or PostgreSQL for lead persistence
- A Laravel application to consume the package
- Leadscaptain API key for real API verification

Optional for production queue monitoring:

- Laravel Horizon

Horizon belongs to the consuming Laravel application and is intentionally not bundled into this package.

---

## Package Architecture

The package follows a simple Onion/DDD-style structure:

```text
src/
├── Application/
│   ├── DTOs/
│   └── Services/
├── Console/
│   └── Commands/
├── Domain/
│   ├── Entities/
│   ├── Events/
│   └── Repositories/
└── Infrastructure/
    ├── Http/
    ├── Notifications/
    ├── Persistence/
    │   ├── Models/
    │   └── Repositories/
    └── Queue/
```

### Domain

Contains business concepts without infrastructure concerns:

- `Lead`
- `LeadRepository`
- `LeadImported`
- `LeadImportFailed`

### Application

Contains application-level operations:

- `LeadData`
- `ImportLeads`

### Infrastructure

Contains Laravel and external-system implementations:

- Leadscaptain HTTP client
- Eloquent persistence
- queue jobs
- notifications
- logging

### Presentation

Contains the package Artisan command:

```text
leadscaptain:import
```

---

# Installation

The package can be installed directly from the Git repository.

Add the repository to the consuming Laravel application's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/vickydevhub/leadscaptain-laravel.git"
        }
    ]
}
```

Then install the package:

```bash
composer require leadscaptain/laravel-leadscaptain:dev-master
```

Laravel automatically discovers the package service provider.

If automatic discovery is disabled, register:

```php
Leadscaptain\LaravelLeadscaptain\LeadscaptainServiceProvider::class,
```

---

# Configuration

Publish the package configuration:

```bash
php artisan vendor:publish \
    --tag=leadscaptain-config
```

This creates:

```text
config/leadscaptain.php
```

Add the following environment variables:

```env
LEADSCAPTAIN_API_KEY=
LEADSCAPTAIN_BASE_URL=https://api.leadscaptain.com
LEADSCAPTAIN_TIMEOUT=30
LEADSCAPTAIN_RETRY_TIMES=3
LEADSCAPTAIN_PER_PAGE=100
LEADSCAPTAIN_CONCURRENCY=10
LEADSCAPTAIN_MAX_PAGE=1000

LEADSCAPTAIN_QUEUE_CONNECTION=redis
LEADSCAPTAIN_QUEUE=leadscaptain
```

## Configuration reference

| Variable | Default | Description |
|---|---:|---|
| `LEADSCAPTAIN_API_KEY` | none | Leadscaptain API key |
| `LEADSCAPTAIN_BASE_URL` | `https://api.leadscaptain.com` | API base URL |
| `LEADSCAPTAIN_TIMEOUT` | `30` | HTTP timeout in seconds |
| `LEADSCAPTAIN_RETRY_TIMES` | `3` | Maximum HTTP retry attempts |
| `LEADSCAPTAIN_PER_PAGE` | `100` | Leads requested per API page |
| `LEADSCAPTAIN_CONCURRENCY` | `10` | Recommended queue/Horizon concurrency |
| `LEADSCAPTAIN_MAX_PAGE` | `1000` | Safety limit for pagination |
| `LEADSCAPTAIN_QUEUE_CONNECTION` | `redis` | Laravel queue connection |
| `LEADSCAPTAIN_QUEUE` | `leadscaptain` | Queue name |

---

# Database

The package automatically loads its migration through the package service provider.

Run:

```bash
php artisan migrate
```

The package creates a `leads` table containing:

- internal database ID
- Leadscaptain external ID
- first name
- last name
- email
- phone
- original API attributes
- timestamps

The `external_id` column is unique.

This makes imports idempotent: importing the same Leadscaptain lead again updates the existing record instead of creating a duplicate.

---

# Queue Processing

The import is intentionally asynchronous.

Run:

```bash
php artisan leadscaptain:import
```

The command does not synchronously import every lead. It dispatches the import process to the configured queue.

The processing flow is:

```text
leadscaptain:import
        |
        v
FetchLeadsJob
        |
        | GET /leads?page=1&per_page=100
        v
Read meta.last_page
        |
        v
Create one FetchLeadPageJob per remaining page
        |
        v
Laravel Batch
        |
        v
Redis queue
        |
        +---- Page 2
        +---- Page 3
        +---- Page 4
        +---- ...
```

Page 1 is fetched first because it provides the `last_page` value.

Pages 2 through the final page are then dispatched as independent jobs.

---

# Queue Concurrency

The package does not install Laravel Horizon because Horizon is an application-level queue monitoring and worker-management tool.

The consuming Laravel application can install Horizon:

```bash
composer require laravel/horizon
php artisan horizon:install
```

Configure Horizon in the consuming application:

```php
'environments' => [
    'production' => [
        'leadscaptain' => [
            'connection' => 'redis',
            'queue' => ['leadscaptain'],
            'balance' => 'auto',
            'minProcesses' => 1,
            'maxProcesses' => 10,
        ],
    ],
],
```

Start Horizon:

```bash
php artisan horizon
```

For local development:

```bash
php artisan horizon
```

The important part is:

```php
'queue' => ['leadscaptain'],
'maxProcesses' => 10,
```

This allows the independent page jobs to be processed concurrently while keeping the concurrency limit under control.

The value can be increased within the infrastructure capacity, for example to 20.

The package itself remains independent of Horizon.

---

# Laravel Queue Batch

The package uses Laravel's queue batching functionality for the page jobs.

The consuming application must have Laravel's `job_batches` table.

For a normal Laravel application, create it with:

```bash
php artisan queue:batches-table
php artisan migrate
```

The package creates a batch containing the individual page jobs:

```php
Bus::batch($jobs)
    ->name('Leadscaptain lead import')
    ->dispatch();
```

This allows the consuming application to track the batch and its completion/failure state.

---

# Retry and Error Handling

The Leadscaptain HTTP client retries transient failures.

The configured retry count is:

```env
LEADSCAPTAIN_RETRY_TIMES=3
```

The retry delays are:

```text
1 second
5 seconds
30 seconds
```

The client retries:

- connection failures
- HTTP `429`
- HTTP `500` and other `5xx` responses

Each page job also has three queue attempts:

```php
public int $tries = 3;

public array $backoff = [
    1,
    5,
    30,
];
```

This means a failed page can be retried independently without restarting the entire import.

---

# Failure Handling

If a page permanently fails after its queue attempts, the package:

1. Logs the failure to the dedicated `leadscaptain` log channel.
2. Dispatches the `LeadImportFailed` domain event.
3. Allows the consuming Laravel application to attach its own listeners/notification behavior.

The package also provides:

```text
LeadImportFailedNotification
```

which can be used by the consuming application for email notification.

Example event listener:

```php
Event::listen(
    LeadImportFailed::class,
    function (LeadImportFailed $event): void {
        // Notify the operations team.
    },
);
```

---

# Logging

The package uses a dedicated Laravel logging channel:

```text
leadscaptain
```

The default log file is:

```text
storage/logs/leadscaptain.log
```

HTTP failures include useful operational information such as:

- page number
- response status
- request duration
- exception message

This makes API and queue failures easier to investigate.

---

# Idempotency

Lead records are identified by the Leadscaptain external ID:

```text
external_id
```

The database has a unique index on this field.

Persistence uses Laravel's `updateOrCreate()` behavior.

Therefore:

```text
same external lead
        |
        v
existing database record
        |
        v
UPDATE
```

instead of creating duplicate records.

This makes retries safe.

---

# API Client

The package uses Laravel's HTTP client.

The client:

- uses the configured API base URL
- sends the API key using `X-API-Key`
- requests JSON
- applies the configured timeout
- retries transient errors
- validates the response structure
- returns the API page data and metadata

The expected API request is:

```text
GET /leads?page={page}&per_page={perPage}
```

The first page is used to determine:

```text
meta.last_page
```

A configurable maximum page limit prevents an unexpectedly large API response from creating an uncontrolled number of jobs.

---

# Artisan Command

Start an import:

```bash
php artisan leadscaptain:import
```

Expected output:

```text
Leadscaptain lead import has been queued.
```

The command is intentionally non-blocking.

The actual API import happens through the queue.

---

# Events

The package defines domain events that provide extension points for future integrations.

Current events include:

```text
LeadImported
LeadImportFailed
```

`LeadImportFailed` is emitted when a page job permanently fails.

The domain event structure is intentionally simple so a consuming application can connect listeners to other infrastructure, including future gRPC-based services.

---

# Docker Development Environment

The repository includes Docker support for package development.

Services:

```text
app
mysql
redis
```

The PHP container uses:

```text
PHP 8.4
```

The MySQL container uses:

```text
MySQL 8.4
```

The Redis container uses:

```text
Redis 7
```

Start the environment:

```bash
docker compose up -d --build
```

Open the application container:

```bash
docker compose exec app bash
```

Install dependencies:

```bash
composer install
```

The package itself does not contain a Laravel `artisan` application.

Docker is provided for package development and automated testing infrastructure.

---

# Testing

The package uses PHPUnit and Orchestra Testbench.

Tests are located under:

```text
tests/
├── Unit/
└── Feature/
```

The package does not require a separate demo Laravel application for its test suite.

Run tests:

```bash
composer test
```

Or through Docker:

```bash
docker compose exec app composer test
```

The test suite covers areas including:

- domain objects
- application services
- API client behavior
- pagination
- retries
- persistence
- queue jobs
- batch dispatching
- command behavior
- failure events
- configuration

HTTP tests use Laravel's `Http::fake()` so they do not require a real Leadscaptain API key.

---

# Code Quality

Laravel Pint is used for formatting:

```bash
composer lint
```

PHPStan is used for static analysis:

```bash
composer analyse
```

Run the complete local quality check:

```bash
composer test
composer lint
composer analyse
```

The same checks are executed by GitHub Actions.

---

# GitHub Actions

The repository contains:

```text
.github/workflows/tests.yml
```

The workflow runs on:

- pushes to `master`
- pull requests targeting `master`

The workflow performs:

1. Checkout
2. PHP 8.4 setup
3. Composer validation
4. Composer dependency installation
5. PHPUnit tests
6. Laravel Pint
7. PHPStan

Example commands executed by CI:

```bash
composer validate --strict
composer install --prefer-dist --no-interaction --no-progress
composer test
composer lint
composer analyse
```

---

# Real API Verification

Automated tests use mocked HTTP responses.

A real Leadscaptain API verification requires a valid API key provided separately by the client.

Once the API key is available, configure:

```env
LEADSCAPTAIN_API_KEY=your-real-api-key
```

Then run the import from the consuming Laravel application:

```bash
php artisan leadscaptain:import
```

Verify:

1. the request reaches Leadscaptain
2. page 1 returns pagination metadata
3. remaining pages are queued
4. workers process the page jobs
5. leads are stored in the database
6. the `leadscaptain` log contains request/processing information

The real API key must never be committed to Git.

---

# Security

Never commit:

```text
.env
.env.*
API keys
access tokens
passwords
private credentials
```

Use environment variables or the consuming application's secret-management system.

The package configuration reads the Leadscaptain API key from:

```env
LEADSCAPTAIN_API_KEY
```

---

# Repository Development

Clone the repository:

```bash
git clone https://github.com/vickydevhub/leadscaptain-laravel.git
cd leadscaptain-laravel
```

Install dependencies:

```bash
composer install
```

Run tests:

```bash
composer test
```

Run formatting check:

```bash
composer lint
```

Run static analysis:

```bash
composer analyse
```

---

# Project Structure

```text
leadscaptain-laravel/
├── .github/
│   └── workflows/
│       └── tests.yml
├── config/
│   └── leadscaptain.php
├── database/
│   └── migrations/
├── docker/
│   └── php/
│       └── Dockerfile
├── src/
│   ├── Application/
│   ├── Console/
│   ├── Domain/
│   └── Infrastructure/
├── tests/
│   ├── Feature/
│   └── Unit/
├── composer.json
├── composer.lock
├── docker-compose.yml
├── phpstan.neon
├── phpunit.xml
└── README.md
```

---

# Current Implementation Notes

The package intentionally keeps responsibilities separated:

```text
Domain
  ↓
Application
  ↓
Infrastructure
```

The package does not contain:

- a standalone Laravel application
- `artisan`
- Laravel routes
- a web UI
- Horizon as a package dependency
- a hard-coded API key

The consuming Laravel application provides the application runtime, queue workers, Horizon, environment configuration, and production infrastructure.

---

# Limitations

The following requires client/environment-specific setup:

- real Leadscaptain API verification requires the client's API key
- production Horizon configuration belongs to the consuming Laravel application
- production Redis/database credentials belong to the consuming application
- production deployment infrastructure is environment-specific

---

# License

MIT

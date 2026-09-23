# Laravel Leadscaptain

A reusable Laravel package for integrating with the Leadscaptain API and importing leads asynchronously.

The package is designed to be installed into an existing Laravel application. It is **not a standalone Laravel application**.

Repository:

https://github.com/vickydevhub/leadscaptain-laravel.git

Branch:

`master`

---

## Features

- Leadscaptain API integration
- Paginated `/leads` API support
- Page 1 metadata discovery
- Background queue processing
- Independent queue job per API page
- Configurable queue concurrency
- Redis queue support
- Laravel Bus batches
- HTTP retries for rate limits and server errors
- Exponential retry backoff
- Idempotent lead persistence
- Domain entities and repository contracts
- Application services and DTOs
- Eloquent persistence implementation
- Domain events
- Dedicated Leadscaptain logging channel
- Failure events for permanently failed imports
- Artisan import command
- PHPUnit and Orchestra Testbench tests
- Docker development environment
- PHP 8.4 support
- Laravel 12 support

---

# Requirements

The package requires:

- PHP 8.4+
- Laravel 12+
- Redis for queue processing
- MySQL or PostgreSQL for lead persistence

---

# Installation

Install the package from Git.

From an existing Laravel application:

```bash
composer config repositories.leadscaptain vcs https://github.com/vickydevhub/leadscaptain-laravel.git
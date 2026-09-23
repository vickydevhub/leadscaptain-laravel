<?php

declare(strict_types=1);

namespace Leadscaptain\LaravelLeadscaptain\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

final class LeadImportFailedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Throwable $exception,
        private readonly int $page,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Leadscaptain lead import failed')
            ->line(
                "Leadscaptain page {$this->page} failed after retries."
            )
            ->line(
                $this->exception->getMessage()
            );
    }
}

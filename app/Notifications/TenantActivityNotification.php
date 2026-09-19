<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class TenantActivityNotification extends Notification
{
    public function __construct(
        public string $type,
        public string $title,
        public string $message,
        public ?string $url = null,
        public ?int $invoiceId = null,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array{type: string, title: string, message: string, url: ?string, invoice_id: ?int} */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'invoice_id' => $this->invoiceId,
        ];
    }
}

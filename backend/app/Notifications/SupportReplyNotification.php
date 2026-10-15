<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Notifications\Notification;

/** The support team answered a customer's ticket. */
class SupportReplyNotification extends Notification
{
    public function __construct(private readonly SupportTicket $ticket, private readonly string $body) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    /** @return array{title: string, body: string, data: array<string, string>} */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'رد من الدعم الفني',
            'body' => $this->body,
            'data' => ['type' => 'support_ticket', 'support_ticket_id' => $this->ticket->uuid],
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'support_ticket',
            'title' => 'رد من الدعم الفني',
            'support_ticket_uuid' => $this->ticket->uuid,
            'message' => $this->body,
            'subject' => $this->ticket->subject,
        ];
    }
}

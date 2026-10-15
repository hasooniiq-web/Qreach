<?php

namespace App\Notifications;

use App\Models\ContactRequest;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Notifications\Notification;

/**
 * The owner answered a conversation that another customer started from the app:
 * the person who scanned the code is told, without learning anything about the owner.
 */
class ContactReplyNotification extends Notification
{
    public function __construct(private readonly ContactRequest $contactRequest, private readonly string $body) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    /** @return array{title: string, body: string, data: array<string, string>} */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'رد من صاحب المركبة',
            'body' => $this->body,
            'data' => ['type' => 'contact_request', 'contact_request_id' => $this->contactRequest->uuid],
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'contact_request',
            'title' => 'رد من صاحب المركبة',
            'contact_request_uuid' => $this->contactRequest->uuid,
            'contact_request_id' => $this->contactRequest->id,
            'vehicle_id' => $this->contactRequest->vehicle_id,
            'message' => $this->body,
        ];
    }
}

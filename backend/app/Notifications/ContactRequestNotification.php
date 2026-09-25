<?php

namespace App\Notifications;

use App\Models\ContactRequest;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Notifications\Notification;

/**
 * Section 14: the owner is notified of a contact request without the
 * visitor ever seeing the owner's real phone number.
 */
class ContactRequestNotification extends Notification
{
    public function __construct(private readonly ContactRequest $contactRequest, private readonly ?string $followUp = null) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    /** @return array{title: string, body: string, data: array<string, string>} */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => $this->followUp ? 'رد جديد من الزائر' : 'رسالة جديدة بخصوص مركبتك',
            'body' => $this->followUp ?? $this->contactRequest->message,
            'data' => ['type' => 'contact_request', 'contact_request_id' => $this->contactRequest->uuid],
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'contact_request',
            'title' => $this->followUp ? 'رد جديد من الزائر' : 'طلب تواصل جديد',
            'contact_request_uuid' => $this->contactRequest->uuid,
            'contact_request_id' => $this->contactRequest->id,
            'vehicle_id' => $this->contactRequest->vehicle_id,
            'message' => $this->followUp ?? $this->contactRequest->message,
            'visitor_name' => $this->contactRequest->visitor_name,
        ];
    }
}

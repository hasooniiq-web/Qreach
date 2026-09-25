<?php

namespace App\Notifications;

use App\Models\ContactRequest;
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
        return ['database'];
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

<?php

namespace App\Notifications\Channels;

use App\Services\FcmSender;
use Illuminate\Notifications\Notification;

/** Delivers a notification's `toFcm()` payload as a push, if Firebase is configured. */
class FcmChannel
{
    public function __construct(private readonly FcmSender $fcm) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toFcm')) {
            return;
        }

        ['title' => $title, 'body' => $body, 'data' => $data] = $notification->toFcm($notifiable);

        $this->fcm->sendToUser($notifiable, $title, $body, $data);
    }
}

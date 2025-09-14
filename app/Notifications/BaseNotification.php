<?php

namespace App\Notifications;

use Benwilkins\FCM\FcmMessage;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification
{
    /**
     * Get the base data for FCM notifications
     *
     * @param mixed $notifiable
     * @param array $specificData
     * @return array
     */
    protected function getBaseData($notifiable, $specificData = []): array
    {
        return array_merge([
            'icon' => $this->getIconUrl(),
            'click_action' => "FLUTTER_NOTIFICATION_CLICK",
            'id' => get_class($this),
            'status' => 'done',
            'recipient_type' => $this->getRecipientType($notifiable),
        ], $specificData);
    }

    /**
     * Determine the recipient type based on user roles
     *
     * @param mixed $notifiable
     * @return string
     */
    protected function getRecipientType($notifiable): string
    {
        if ($notifiable->hasRole('salon owner')) {
            return 'salon';
        } elseif ($notifiable->hasRole('customer')) {
            return 'client';
        }
        return 'unknown';
    }

    /**
     * Get the FCM message with standardized structure
     *
     * @param mixed $notifiable
     * @param string $title
     * @param string $body
     * @param array $data
     * @return FcmMessage
     */
    protected function getFcmMessage($notifiable, string $title, string $body, array $data = []): FcmMessage
    {
        $message = new FcmMessage();
        $notification = [
            'title' => $title,
            'body' => $body,
        ];

        $baseData = $this->getBaseData($notifiable, $data);
        
        $message->content($notification)->data($baseData)->priority(FcmMessage::PRIORITY_HIGH);

        if ($to = $notifiable->routeNotificationFor('fcm', $this)) {
            $message->to($to);
        }

        return $message;
    }

    /**
     * Get the icon URL for the notification
     *
     * @return string
     */
    abstract protected function getIconUrl(): string;
}
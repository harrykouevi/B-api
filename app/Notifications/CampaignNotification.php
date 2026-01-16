<?php
/*
 * File name: CampaignNotification.php
 * Last modified: 2025.03.09
 * Author: CHARM Platform
 */

namespace App\Notifications;

use Benwilkins\FCM\FcmMessage;
use Illuminate\Bus\Queueable;

class CampaignNotification extends BaseNotification
{
    use Queueable;

    private string $title;
    private string $message;
    private string $audience;
    private bool $sendToTopic;
    private string $topicName;

    public function __construct(string $title, string $message, string $audience = 'all', bool $sendToTopic = false, string $topicName = 'all')
    {
        $this->title = $title;
        $this->message = $message;
        $this->audience = $audience;
        $this->sendToTopic = $sendToTopic;
        $this->topicName = $topicName;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via(mixed $notifiable): array
    {
        return ['fcm'];
    }

    /**
     * Get the fcm representation of the notification.
     *
     * @param mixed $notifiable
     * @return FcmMessage
     */
    public function toFcm(mixed $notifiable): FcmMessage
    {
        $data = [
            'campaign' => '1',
            'title' => $this->title,
            'message' => $this->message,
            'audience' => $this->audience,
        ];

        $message = $this->getFcmMessage($notifiable, $this->title, $this->message, $data);
        if ($this->sendToTopic) {
            $message->to($this->topicName, true);
        }

        return $message;
    }

    protected function getIconUrl(): string
    {
        return asset('images/logo_default.png');
    }

    protected function getRecipientType($notifiable): string
    {
        return $this->audience;
    }
}

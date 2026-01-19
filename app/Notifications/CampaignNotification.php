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
    private string $messageRich;
    private string $messageFormat;
    private ?string $imageUrl;
    private ?int $campaignId;
    private ?string $actionType;
    private ?string $deepLink;
    private ?string $ctaText;
    private string $audience;
    private bool $sendToTopic;
    private string $topicName;

    public function __construct(
        string $title,
        string $message,
        string $audience = 'all',
        bool $sendToTopic = false,
        string $topicName = 'all',
        string $messageRich = '',
        string $messageFormat = 'plain',
        ?string $imageUrl = null,
        ?int $campaignId = null,
        ?string $actionType = null,
        ?string $deepLink = null,
        ?string $ctaText = null
    )
    {
        $this->title = $title;
        $this->message = $message;
        $this->audience = $audience;
        $this->sendToTopic = $sendToTopic;
        $this->topicName = $topicName;
        $this->messageRich = $messageRich;
        $this->messageFormat = $messageFormat;
        $this->imageUrl = $imageUrl;
        $this->campaignId = $campaignId;
        $this->actionType = $actionType;
        $this->deepLink = $deepLink;
        $this->ctaText = $ctaText;
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
        $body = $this->message !== '' ? $this->message : ' ';
        $data = [
            'campaign' => '1',
            'title' => $this->title,
            'message' => $body,
            'message_rich' => $this->messageRich,
            'message_format' => $this->messageFormat,
            'image_url' => $this->imageUrl ?? '',
            'campaign_id' => $this->campaignId ? (string) $this->campaignId : '',
            'action_type' => $this->actionType ?? '',
            'deep_link' => $this->deepLink ?? '',
            'cta_text' => $this->ctaText ?? '',
            'audience' => $this->audience,
        ];

        $message = $this->getFcmMessage($notifiable, $this->title, $body, $data);
        if (!empty($this->imageUrl)) {
            $message->content([
                'title' => $this->title,
                'body' => $body,
                'image' => $this->imageUrl,
            ]);
            $message->apns([
                'payload' => [
                    'aps' => [
                        'mutable-content' => 1,
                        'alert' => [
                            'title' => $this->title,
                            'body' => $body,
                        ],
                    ],
                ],
                'fcm_options' => [
                    'image' => $this->imageUrl,
                ],
            ]);
        } else {
            $message->apns([
                'payload' => [
                    'aps' => [
                        'alert' => [
                            'title' => $this->title,
                            'body' => $body,
                        ],
                    ],
                ],
            ]);
        }
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

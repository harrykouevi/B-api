<?php

namespace App\Notifications;

use App\Models\Story;
use Benwilkins\FCM\FcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;

class MyStoryIsReadyNotification extends BaseNotification
{
    use Queueable;

    // private Story $story;
    // private string $message;

    public function __construct( private Story $story )
    {}

    /**
     * Canaux d'envoi
     */
    public function via(mixed $notifiable): array
    {
        $types = ['database'];
        if (setting('enable_notifications', false)) {
            $types[] = 'fcm';
        }
        return $types;
    }

    /**
     * Création du message FCM en utilisant la logique de BaseNotification
     */
    public function toFcm($notifiable): FcmMessage
    {
        $title = trans('lang.notification_ready_story_title');
        $message = trans('lang.notification_ready_story_message') ;

        // Données différenciées selon le type de destinataire
        $baseData = [
            'story_id' => (string) $this->story->uuid,
            'type'    => 'story_is_ready',
            'author_id' => (string) $this->story->user_id,
            'image' => $this->getIconUrl(),
        ];

        // donnees specifiques
        $data = array_merge($baseData, []);

        return $this->getFcmMessage($notifiable, $title, $message, $data);
    }

    /**
     * Obligatoire car défini en "abstract" dans BaseNotification
     * On définit l'image qui apparaîtra dans la petite icône de notification
     */
    protected function getIconUrl(): string
    {
        Log::info(['fqdfdfqf has media',$this->story->hasMedia('*')]) ;
        if ($this->story->hasMedia('*')) {
            return $this->story->getFirstMediaUrl('*', 'thumb');
        }
        
        return asset('images/logo_default.png'); // Assure-toi que ce fichier existe
    }

    /**
     * Stockage en base de données pour l'historique (la cloche)
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'story_id'     => (string) $this->story->uuid,
            'author_id'   => (string) $this->story->user_id,
            'author_name' => $this->story->user->name ?? 'Un utilisateur',
            'message'     => trans('lang.notification_ready_story_message') ,
            'image'       => $this->getIconUrl(),
            'type'        => 'story_is_ready',
        ];
    }
}
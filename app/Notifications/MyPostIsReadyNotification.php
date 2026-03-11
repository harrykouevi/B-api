<?php

namespace App\Notifications;

use App\Models\Post;
use Benwilkins\FCM\FcmMessage;
use Illuminate\Bus\Queueable;

class MyPostIsReadyNotification extends BaseNotification
{
    use Queueable;

    // private Post $post;
    // private string $message;

    public function __construct( private Post $post )
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
        $title = trans('lang.notification_ready_post_title');
        $message = trans('lang.notification_ready_post_message') ;

        // Données différenciées selon le type de destinataire
        $baseData = [
            'post_id' => (string) $this->post->uuid,
            'type'    => 'post_is_ready',
            'author_id' => (string) $this->post->author_id,
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
        if ($this->post->hasMedia('*')) {
            return $this->post->getFirstMediaUrl('*', 'thumb');
        }
        
        return asset('images/logo_default.png'); // Assure-toi que ce fichier existe
    }

    /**
     * Stockage en base de données pour l'historique (la cloche)
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'post_id'     => (string) $this->post->uuid,
            'author_id'   => (string) $this->post->author_id,
            'author_name' => $this->post->author->name ?? 'Un utilisateur',
            'message'     => trans('lang.notification_ready_post_message') ,
            'image'       => $this->getIconUrl(),
            'type'        => 'post_is_ready',
        ];
    }
}
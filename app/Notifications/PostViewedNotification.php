<?php

namespace App\Notifications;

use App\Models\PostView;
use Benwilkins\FCM\FcmMessage;
use Illuminate\Bus\Queueable;

class PostViewedNotification extends BaseNotification
{
    use Queueable;

    private PostView $postview;

    public function __construct(PostView $postview)
    {
        $this->postview = $postview;
    }

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
        $title = "Une vue de plus!";
        $body = ($this->postview->user->name ?? 'Un utilisateur') . " vient de voir votre post #". $this->postview->post->uuid;
        
        $data = [
            'post_id' => (string) $this->postview->post->uuid,
            'type'    => 'new_post',
        ];

        return $this->getFcmMessage($notifiable, $title, $body, $data);
    }

    /**
     * Obligatoire car défini en "abstract" dans BaseNotification
     * On définit l'image qui apparaîtra dans la petite icône de notification
     */
    protected function getIconUrl($model = 'post'): string
    {
        if($model == 'user'){ 
            if ($this->postview->user->hasMedia('image')) {
                return $this->postview->user->getFirstMediaUrl('image', 'thumb');
            }
        }
        return asset('images/logo_default.png'); // Assure-toi que ce fichier existe
    }

    /**
     * Stockage en base de données pour l'historique (la cloche)
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'post_id'     => $this->postview->post->uuid,
            'author_name' => $this->post->user->name ?? 'Un utilisateur',
            'message'     => ($this->postview->user->name ?? 'Un utilisateur') . " vient de voir votre post #". $this->postview->post->uuid ,
            'image'       => $this->getIconUrl(),
        ];
    }
}
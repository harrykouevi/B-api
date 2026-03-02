<?php

namespace App\Notifications;

use App\Models\Post;
use Benwilkins\FCM\FcmMessage;
use Illuminate\Bus\Queueable;

class PostPublishedNotification extends BaseNotification
{
    use Queueable;

    private Post $post;

    public function __construct(Post $post)
    {
        $this->post = $post;
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
        $title = "Nouveau post sur Charm !";
        $body = ($this->post->user->name ?? 'Un utilisateur') . " a publié une nouvelle vidéo.";
        
        $data = [
            'post_id' => (string) $this->post->id,
            'type'    => 'new_post',
        ];

        return $this->getFcmMessage($notifiable, $title, $body, $data);
    }

    /**
     * Obligatoire car défini en "abstract" dans BaseNotification
     * On définit l'image qui apparaîtra dans la petite icône de notification
     */
    protected function getIconUrl(): string
    {
        // On essaie de mettre la miniature de la vidéo, sinon une image par défaut
        if (isset($this->post->custom_fields['thumbnail'])) {
            return $this->post->custom_fields['thumbnail'];
        }
        
        return asset('images/logo_default.png'); // Assure-toi que ce fichier existe
    }

    /**
     * Stockage en base de données pour l'historique (la cloche)
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'post_id'     => $this->post->id,
            'author_name' => $this->post->user->name ?? 'Un utilisateur',
            'message'     => ($this->post->user->name ?? 'Quelqu\'un') . " a publié une nouvelle vidéo.",
            'image'       => $this->getIconUrl(),
        ];
    }
}
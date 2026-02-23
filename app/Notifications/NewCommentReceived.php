<?php

namespace App\Notifications;

use App\Models\Comment;
use Benwilkins\FCM\FcmMessage;
use Illuminate\Bus\Queueable;

class NewCommentReceived extends BaseNotification
{
    use Queueable;

    private Comment $comment;

    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
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
     * Envoi à Firebase via le package Benwilkins
     */
    public function toFcm($notifiable): FcmMessage
    {
        $commentatorName = $this->comment->user->name ?? 'Un utilisateur';
        $postTitle = $this->comment->post->title ?? 'votre post';

        $title = "Nouveau commentaire !";
        $body = "{$commentatorName} a commenté votre post : \"{$postTitle}\"";

        $data = [
            'post_id'    => (string) $this->comment->post_id,
            'comment_id' => (string) $this->comment->id,
            'type'       => 'new_comment',
        ];

        return $this->getFcmMessage($notifiable, $title, $body, $data);
    }

    /**
     * Icône de la notification (obligatoire pour BaseNotification)
     */
    protected function getIconUrl(): string
    {
        // On affiche la photo de profil de celui qui a commenté
        if ($this->comment->user && $this->comment->user->hasMedia('avatar')) {
            return $this->comment->user->getFirstMediaUrl('avatar', 'thumb');
        }

        return asset('images/logo_default.png');
    }

    /**
     * Stockage en base de données (pour la cloche de l'app)
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'post_id'          => $this->comment->post_id,
            'comment_id'       => $this->comment->id,
            'commentator_name' => $this->comment->user->name ?? 'Un utilisateur',
            'message'          => "a commented on your post.",
        ];
    }
}
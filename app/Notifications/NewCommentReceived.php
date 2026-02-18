<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewCommentReceived extends Notification
{
    use Queueable;

    public $comment;

    /**
     * Le constructeur reçoit l'objet Commentaire
     */
    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
    }

    /**
     * On définit les canaux : on utilise uniquement 'database'
     * Cela va créer une ligne dans ta table 'notifications'
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * C'est ici qu'on définit ce que l'auteur du post verra sous sa cloche.
     * Ces données seront transformées en JSON dans la colonne 'data' de ta base.
     */
    public function toArray($notifiable)
    {
        return [
            'comment_id'  => $this->comment->id,
            'post_id'     => $this->comment->post_id,
            'author_id'   => $this->comment->user_id,
            'author_name' => $this->comment->user->name, // Le nom de celui qui a commenté
            'title'       => 'Nouveau commentaire',
            'message'     => $this->comment->user->name . ' a commenté votre post.',
            'content'     => "Str"::limit($this->comment->content, 50), // Un extrait du commentaire
        ];
    }
}
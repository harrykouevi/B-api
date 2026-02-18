<?php

namespace App\Notifications;

use App\Models\Post; // Import du modèle Post
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PostPublishedNotification extends Notification
{
    use Queueable;

    public $post; // On déclare la variable pour pouvoir l'utiliser plus bas

    /**
     * Le constructeur doit recevoir le Post créé
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    /**
     * On définit le canal : 'database' pour la cloche dans l'app
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Les données qui seront stockées en JSON dans ta base de données
     */
    public function toArray($notifiable)
    {
        return [
            'post_id'     => $this->post->id,
            'author_name' => $this->post->user->name ?? 'Un utilisateur',
            'title'       => 'Nouveau post publié',
            'message'     => ($this->post->user->name ?? 'Quelqu\'un') . " a publié une nouvelle vidéo.",
            'thumbnail'   => $this->post->custom_fields['thumbnail'] ?? null,
        ];
    }
}
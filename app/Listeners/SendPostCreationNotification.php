<?php

namespace App\Listeners;

use App\Events\PostCreated;
use App\Models\User;
use App\Notifications\PostPublishedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendPostCreationNotification implements ShouldQueue
{
   public function handle(PostCreated $event)
{
    // On récupère le post
    $post = $event->post;

    // On évite d'envoyer la notif si le post n'a pas d'auteur (sécurité)
    if (!$post->user_id) {
        return;
    }

    // On récupère les utilisateurs (Sauf l'auteur)
    // On utilise chunk pour être sûr que ça ne plante jamais, même à 10 000 users
    User::where('id', '!=', $post->user_id)
        ->chunk(100, function ($users) use ($post) {
            Notification::send($users, new PostPublishedNotification($post));
        });
}
}
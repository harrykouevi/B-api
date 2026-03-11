<?php

namespace App\Listeners;

use App\Events\MyPostCreatedEvent;
use App\Models\User;
use App\Notifications\MyPostCreatedNotification;
use App\Notifications\PostPublishedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class MyPostCreatedListener implements ShouldQueue
{
    public function handle(MyPostCreatedEvent $event)
    {
        // On récupère le post
        $post = $event->post;

        // On évite d'envoyer la notif si le post n'a pas d'auteur (sécurité)
        if (!$post->author_id) {
            return;
        }

        // On récupère les utilisateurs qui est  l'auteur)
        // On utilise chunk pour être sûr que ça ne plante jamais, même à 10 000 users
        User::where('id', '==', $post->author_id)
            ->chunk(100, function ($users) use ($post,$event) {
                Notification::send($users, new MyPostCreatedNotification($post , $event->message));
            });
    }
}
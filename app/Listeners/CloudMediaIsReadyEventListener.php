<?php

namespace App\Listeners;

use App\Events\CloudMediaIsReadyEvent;
use App\Models\Post;
use App\Models\User;
use App\Notifications\MyPostIsReadyNotification;
use App\Notifications\PostPublishedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;


class CloudMediaIsReadyEventListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CloudMediaIsReadyEvent $event): void
    {
        if($event->model instanceof Post){
            // On récupère le post
            $post = $event->model;

            // On évite d'envoyer la notif si le post n'a pas d'auteur (sécurité)
            if (!$post->author_id) {
                return;
            }

            if (!$post->author()->hasRole('admin')) {
                $post->author->notify(new MyPostIsReadyNotification($post));
            }

            // On récupère les utilisateurs qui est  l'auteur)
            // On utilise chunk pour être sûr que ça ne plante jamais, même à 10 000 users
            User::where('id', '!=', $post->author_id)
                ->chunk(100, function ($users) use ($post,$event) {
                    Notification::send($users, new  PostPublishedNotification($post));
                });
        }
    }
}

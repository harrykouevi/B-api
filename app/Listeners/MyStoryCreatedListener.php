<?php

namespace App\Listeners;

use App\Events\MyStoryCreatedEvent;
use App\Models\User;
use App\Notifications\MyStoryCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class MyStoryCreatedListener implements ShouldQueue
{
    public function handle(MyStoryCreatedEvent $event)
    {
        // On récupère le story
        $story = $event->story;

        // On évite d'envoyer la notif si le story n'a pas d'auteur (sécurité)
        if (!$story->user_id) {
            return;
        }

        // On récupère les utilisateurs qui est  l'auteur)
        // On utilise chunk pour être sûr que ça ne plante jamais, même à 10 000 users
        $user = User::find($story->user_id);
        Notification::send($user, new MyStoryCreatedNotification($story, $event->message));
   
    }
}
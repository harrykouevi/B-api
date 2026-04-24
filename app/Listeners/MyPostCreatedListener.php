<?php

namespace App\Listeners;

use App\Events\MyPostCreatedEvent;
use App\Models\User;
use App\Notifications\MyPostCreatedNotification;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

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
        $user = User::find($post->author_id);
        NotificationService::notify([$user], new MyPostCreatedNotification($post, $event->message));
    }
}
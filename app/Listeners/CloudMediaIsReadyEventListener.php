<?php

namespace App\Listeners;

use App\Events\CloudMediaIsReadyEvent;
use App\Models\Post;
use App\Models\Story;
use App\Models\User;
use App\Notifications\MyPostIsReadyNotification;
use App\Notifications\MyStoryIsReadyNotification;
use App\Notifications\PostPublishedNotification;
use App\Notifications\StoryPublishedNotification;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
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
        Log::info('xxxxx to owner');

        if($event->model instanceof Post){
            // On récupère le post
            $post = $event->model;
            // On évite d'envoyer la notif si le post n'a pas d'auteur (sécurité)
            if (!$post->author_id) {
                return;
            }

            if (!$post->author->hasRole('admin')) {
                NotificationService::notify([$post->author] , new MyPostIsReadyNotification($post));
                Log::info('send to owner Z');
            }

            
            // On récupère les utilisateurs qui est  l'auteur)
            // On utilise chunk pour être sûr que ça ne plante jamais, même à 10 000 users
            User::where('id', '!=', $post->author_id)
                ->chunk(100, function ($users) use ($post,$event) {
                    NotificationService::notify($users, new  PostPublishedNotification($post));
                });
        }elseif($event->model instanceof Story){
            // On récupère le post
            $story = $event->model;
            Log::info('send to owner');

            // On évite d'envoyer la notif si le story n'a pas d'auteur (sécurité)
            if (!$story->user_id) {
                return;
            }

            if (!$story->user->hasRole('admin')) {
                NotificationService::notify( [$story->user] , new MyStoryIsReadyNotification($story));
                Log::info('send to owner P');
            
            }

            // On récupère les utilisateurs qui est  l'auteur)
            // On utilise chunk pour être sûr que ça ne plante jamais, même à 10 000 users
            User::where('id', '!=', $story->user_id)
                ->chunk(100, function ($users) use ($story,$event) {
                    // NotificationService::notify($users, new  StoryPublishedNotification($story));
               
                });
        }
    }
}

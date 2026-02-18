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
        // 1. On récupère TOUS les utilisateurs (sauf l'auteur du post)
        // On peut filtrer par 'active' si tu as une colonne pour ça
        $users = User::where('id', '!=', $event->post->user_id)->get();

        // 2. Envoi groupé (très optimisé par Laravel)
        Notification::send($users, new PostPublishedNotification($event->post));
    }
}
<?php

namespace App\Listeners;

use App\Events\CommentPosted;
use App\Notifications\NewCommentReceived; // On va la créer juste après
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

// On ajoute ShouldQueue pour que l'envoi ne ralentisse pas l'utilisateur
class SendCommentNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct()
    {
        //
    }

    public function handle(CommentPosted $event)
    {
        // 1. On récupère le commentaire depuis l'événement
        $comment = $event->comment;

        // 2. On récupère l'auteur du POST (celui qui doit recevoir la notif)
        // On remonte du commentaire -> vers le post -> vers l'auteur (user)
        $postAuthor = $comment->post->author;

        // 3. On lui envoie la notification
        // (On vérifie quand même que l'auteur du commentaire n'est pas l'auteur du post)
        if ($postAuthor && $postAuthor->id !== $comment->user_id) {
            $postAuthor->notify(new NewCommentReceived($comment));
        }
    }
}
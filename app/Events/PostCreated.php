<?php

namespace App\Events;

use App\Models\Post;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostCreated
{
    use Dispatchable, SerializesModels;

    public $post; // <--- Indispensable pour stocker le post

    public function __construct(Post $post) // <--- Indispensable pour recevoir le post
    {
        $this->post = $post;
    }
}
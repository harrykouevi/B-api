<?php

namespace App\Events;

use App\Models\Post;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MyPostCreatedEvent
{
    use Dispatchable, SerializesModels;


    public function __construct(public Post $post , public  string $message) // <--- Indispensable pour recevoir le post
    {}
}
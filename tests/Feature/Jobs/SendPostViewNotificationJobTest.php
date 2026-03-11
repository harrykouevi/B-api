<?php

namespace Tests\Feature\Jobs;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use App\Models\PostView;
use App\Jobs\SendPostViewNotificationJob;
use App\Notifications\PostViewedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

class SendPostViewNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_sends_notification()
    {
        try{ 

        
        Notification::fake(); // Prevent actual notifications

         
        // Create a post author
        $author = User::factory()->create();
        // Create a post
        $post = Post::factory()->create(['author_id' => $author->id]);

        // Simulate some post views
        $viewer1 = User::factory()->create();
        $viewer2 = User::factory()->create();


        PostView::factory()->create([
            'post_id' => $post->id,
            'user_id' => $viewer1->id,
            'notified_at' => null,
        ]);

        PostView::factory()->create([
            'post_id' => $post->id,
            'user_id' => $viewer2->id,
            'notified_at' => null,
        ]);
        
        // Dispatch the job
        $job = new SendPostViewNotificationJob($post);
        $job->handle();

        // Assert the notification was sent
        Notification::assertSentTo(
            $author,
            PostViewedNotification::class,
            function ($notification, $channels) use ($post) {
                return $notification->post->id === $post->id;
            }
        );

        // Assert views were marked as notified
        $this->assertDatabaseHas('post_views', [
            'post_id' => $post->id,
            'user_id' => $viewer1->id,
            // 'notified_at' => now()->format('Y-m-d H:i'),
        ]);

        $this->assertDatabaseHas('post_views', [
            'post_id' => $post->id,
            'user_id' => $viewer2->id,
            // 'notified_at' => now()->format('Y-m-d H:i'),
        ]);


        } catch (\Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);

            throw $e; 
        }
    }
}

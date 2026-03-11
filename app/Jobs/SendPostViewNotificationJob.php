<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\PostView;
use App\Notifications\PostViewedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPostViewNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        
        $views = PostView::whereNull('notified_at')
            ->with('user')
            ->limit(1000)
            ->get()
            ->groupBy('post_id');


        foreach ($views as $postId => $recentViews){
            
            $recentViews__ = $recentViews->take(100);
            $post = Post::find($postId);
            $author = $post->author ;
            //$author_name = !is_null($author )? $author->name : 'Un utilisateur' ;
            
            $names = $recentViews__->pluck('user.name')->filter()->toArray();
            $userIds = $recentViews__->pluck('user_id')->filter()->toArray();
            $otherCount = $recentViews->count() - $recentViews__->count();


            $and = __('lang.notification_and');

            if (count($names) === 1) {
                $message = __('lang.notification_post_viewed_single', [
                    'name' => $names[0]
                ]);
            } elseif (count($names) > 1 && $otherCount <= 0) {
                $names = implode(" $and ", $names); // "Alice et Bob"
                $message = __('lang.notification_post_viewed_multiple', [
                    'names' => $names
                ]);
            } else {
                $namesStr = implode(', ', $names);
                $message = __('lang.notification_post_viewed_with_others', [
                    'names' => $namesStr,
                    'count' => $otherCount
                ]);
            }

            // envoyer notification
            $author->notify(new PostViewedNotification($post, $message , $userIds));

            // marquer les vues comme notifiées
            $recentViews__->each(function ($view) {
                $view->notified_at = now();
                $view->save();
            });
        }
    }
}

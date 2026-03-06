<?php
/*
 * File name: Post.php
 * Last modified: 2026.02.03
 * Author: 
 * Copyright (c) 2026
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Spatie\Image\Exceptions\InvalidManipulation;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Concerns\HasUuid;

/**
 * Class Post
 * @package App\Models
 * @version January 19, 2026, 03:59 pm UTC
 *
 * @property string uuid
 * @property string caption
 * @property integer salon_id
 * @property integer author_id
 * @property string vimeo_id
 */
class Post extends Model implements HasMedia
{
    use InteractsWithMedia {
        getFirstMediaUrl as protected getFirstMediaUrlTrait;
    }

    use HasFactory, HasUuid;

    public $table = 'posts';
    
    /**
     * Validation rules
     *
     * @var array
     */
    public static array $rules = [
        'caption' => 'required|string',
        'salon_id' => 'nullable|exists:salons,id',
        'author_id' => 'nullable|exists:users,id',
    ];

    protected $hidden = [
        'id'
    ];

    public array $translatable = [
        'caption',
    ];

    public $fillable = [
        'uuid',
        'caption',
        'salon_id',
        'author_id',
        'vimeo_id', // Ajouté pour permettre l'enregistrement massif
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'caption' => 'string',
        'author_id' => 'integer',
        'salon_id' => 'integer',
        'vimeo_id' => 'string', // Cast en string pour la sécurité des IDs longs
    ];

    /**
     * New Attributes
     *
     * @var array
     */
    protected $appends = [
        'has_media',
        'like_count', //   nombre de likes
        'is_liked',  
        'is_favorite',
        'favory_count',
        'view_count',
        'comment_count'

    ];
             // L'Accessor pour le nombre de likes
    public function getLikeCountAttribute()
    {
        return Like::where('post_id', $this->id)->count();
    }

    // L'Accessor pour savoir si l'utilisateur actuel a aimé
    public function getIsLikedAttribute()
    {
        if (!auth()->check()) return false;
        return Like::where('post_id', $this->id)
                   ->where('user_id', auth()->id())
                   ->exists();
    }

    protected static function boot(): void
    {
        parent::boot();

        // Avant la création
        static::creating(function ($post) {
            $post->slug = Str::slug($post->caption);
            if (!$post->uuid) {
                $post->uuid = (string) Str::uuid();
            }
        });

        // Avant la mise à jour
        static::updating(function ($s) {
            $s->slug = Str::slug($s->caption);
        });
    }

  

    /**
     * @param Media|null $media
     * @throws InvalidManipulation
     */
    public function registerMediaConversions(Media $media = null) :void
    {
        $this->addMediaConversion('thumb')
            ->fit(Manipulations::FIT_CROP, 200, 200)
            ->sharpen(10);

        $this->addMediaConversion('icon')
            ->fit(Manipulations::FIT_CROP, 100, 100)
            ->sharpen(10);
    }

    /**
     * to generate media url in case of fallback will
     * return the file type icon
     * @param string $collectionName
     * @param string $conversion
     * @return string url
     */
    public function getFirstMediaUrl($collectionName = 'default', string $conversion = ''): string
    {
        $collectionName = '*';
        $media = $this->getFirstMedia('*');
        
        if($media != null && $media->disk === 'r2'){ 
            $streamUid = $media->custom_properties['stream_uid'] ?? null;
            if ((str_starts_with($media->mime_type, 'video/') || str_starts_with($media->mime_type, 'application/')) 
                &&  !empty($streamUid) ) {
                return  "https://customer-jhmjx2xxk4rdo62d.cloudflarestream.com/{$streamUid}/manifest/video.m3u8";
               
            }
            return  Storage::disk('r2')->temporaryUrl(
                $media->getPathRelativeToRoot($conversion),
                now()->addMinutes(10)
            ); 
        } 
        
        $url = $this->getFirstMediaUrlTrait($collectionName);
        if (!$url) return ''; // Sécurité si pas d'URL
        $array = explode('.', $url);
        $extension = strtolower(end($array));
        if (in_array($extension, config('media-library.extensions_has_thumb'))) {
            return asset($this->getFirstMediaUrlTrait($collectionName, $conversion));
        } else {
            return asset(config('media-library.icons_folder') . '/' . $extension . '.png');
        }
    }


    

    /**
     * Add Media to api results
     * @return bool
     */
    public function getHasMediaAttribute(): bool
    {
        return $this->hasMedia('*');
    }

    /**
     * @return BelongsTo
     **/
    public function author(): BelongsTo
    {
        // Correction : On utilise généralement User::class ici si author_id pointe vers users
        return $this->belongsTo(User::class, 'author_id', 'id');
    }

    /**
     * @return BelongsTo
     **/
    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class, 'salon_id', 'id');
    }

 
    /**
     * A post can have many targets (polymorphic)
     */
    public function targets()
    {
        return $this->hasMany(PostTarget::class);
    }

    /**
     * A post can have many reports (polymorphic)
     */
    public function reports()
    {
        return $this->morphMany(Report::class, 'model');
    }

    /**
     * Shortcut: retrieve only the real target models
     * (Service, Event, etc.)
     */
    public function targetModels()
    {
        return $this->targets->map(fn ($target) => $target->model);
    }

    /**
     * Relation avec les Likes
     */
    public function likes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    
    public function getCommentCountAttribute()
    {
        return $this->comments()->count();
    }

    public function getViewCountAttribute()
    {
        return $this->views()->count();
    }

    /**
     * Check if is a favorite for current user
     * @return bool
     */
    public function getIsFavoriteAttribute(): bool
    {
        return $this->favorites()->count() > 0;
    }


    /**
     * @return integer
     */
    public function getFavoryCountAttribute()
    {
        return $this->favories()->count() ;
    }



    /**
     * @return HasMany
     **/
    public function favorites(): HasMany
    {
        return $this->hasMany(FavoritePost::class, 'post_id')->where('favorite_posts.user_id', auth()->id());
    }

    /**
     * @return HasMany
     **/
    public function favories(): HasMany
    {
        return $this->hasMany(FavoritePost::class, 'post_id');
    }


   
    /**
     * @return HasMany
     **/
    public function views()
    {
        return $this->hasMany(PostView::class);
    }

    /**
     * @return BelongsTo
     **/
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id', 'id');
    }

}

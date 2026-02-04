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
        'vimeo_id'  => 'nullable|string', // Règle pour l'ID Vimeo
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
        'vimeo_id' // Ajouté pour permettre l'enregistrement massif
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
        'vimeo_embed_url', // Accesseur pour l'iframe
    ];

    protected static function boot(): void
    {
        parent::boot();

        // Avant la création
        static::creating(function ($s) {
            $s->slug = Str::slug($s->caption);
        });

        // Avant la mise à jour
        static::updating(function ($s) {
            $s->slug = Str::slug($s->caption);
        });
    }

    /**
     * Accesseur pour générer l'URL d'intégration Vimeo
     * @return string|null
     */
    public function getVimeoEmbedUrlAttribute(): ?string
    {
        return $this->vimeo_id 
            ? "https://player.vimeo.com/video/{$this->vimeo_id}" 
            : null;
    }

    protected static function booted()
    {
        static::creating(function ($post) {
            if (!$post->uuid) {
                $post->uuid = (string) Str::uuid();
            }
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
        return $this->hasMedia('image');
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
     * Shortcut: retrieve only the real target models
     * (Service, Event, etc.)
     */
    public function targetModels()
    {
        return $this->targets->map(fn ($target) => $target->model);
    }

   
   
 
}

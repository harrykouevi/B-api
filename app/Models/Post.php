<?php
/*
 * File name: Post.php
 * Last modified: 2026.01.19 at 15:41:01
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
 * @property string caption
 * @property integer salon_id
 * @property integer author_id
 */
class Post extends Model implements HasMedia
{
    use InteractsWithMedia {
        getFirstMediaUrl as protected getFirstMediaUrlTrait;
    }

    use HasFactory , HasUuid ;

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
        'author_id'
    ];

   
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'image' => 'string',
        'caption' => 'string',
        'author_id' => 'integer',
        'salon_id' => 'integer',
    ];
    /**
     * New Attributes
     *
     * @var array
     */
    protected $appends = [
        'has_media',
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
        return $this->belongsTo(User::class, 'author_id', 'id');
    }

    /**
     * @return BelongsTo
     **/
    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class, 'salon_id', 'id');
    }

   
 
}

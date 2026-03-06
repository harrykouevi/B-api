<?php
/*
 * File name: Story.php
 * Last modified: 2026.03.02
 * Author: Gemini
 * Copyright (c) 2026
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Concerns\HasUuid;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Class Story
 * @package App\Models
 *
 * @property string uuid
 * @property integer user_id
 * @property string type
 * @property string expires_at
 */
class Story extends Model implements HasMedia
{
    use InteractsWithMedia {
        getFirstMediaUrl as protected getFirstMediaUrlTrait;
    }

    use HasFactory, HasUuid;

    public $table = 'stories';

    /**
     * Validation rules
     *
     * @var array
     */
    public static array $rules = [
        'user_id' => 'required|exists:users,id',
        'salon_id' => 'nullable|exists:salons,id',

    ];

    protected $hidden = [
        'id'
    ];

    public $fillable = [
        'uuid',
        'user_id',
        'salon_id',
        'expires_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        
        'user_id' => 'integer',
        'salon_id' => 'integer',
    ];

  

    /**
     * New Attributes
     *
     * @var array
     */
    protected $appends = [
        'has_media',
        'is_expired'
    ];

    /**
     * Boot function from Laravel.
     */
    protected static function booted()
    {
        static::creating(function ($story) {
            if (!$story->uuid) {
                $story->uuid = (string) Str::uuid();
            }
            // Définit l'expiration à 24h par défaut si non renseigné
            if (!$story->expires_at) {
                $story->expires_at = Carbon::now()->addHours(24);
            }
        });
    }

    /**
     * Accesseur pour savoir si la story est expirée
     * @return bool
     */
    public function getIsExpiredAttribute(): bool
    {
        return Carbon::now()->greaterThan($this->expires_at);
    }

    /**
     * Spatie MediaLibrary : Conversions
     * @param Media|null $media
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Manipulations::FIT_CROP, 200, 200)
            ->sharpen(10);
    }

    /**
     * Custom getFirstMediaUrl to handle fallback icons
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
        return $this->hasMedia('*') ;
    }

    /**
     * @return BelongsTo
     **/
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }


    /**
     * A post can have many reports (polymorphic)
     */
    public function reports()
    {
        return $this->morphMany(Report::class, 'model');
    }
}
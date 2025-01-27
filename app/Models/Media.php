<?php
/*
 * File name: Media.php
 * Last modified: 2024.04.18 at 17:21:44
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;


/**
 * @property mixed size
 */
class Media extends BaseMedia implements HasMedia
{
    use InteractsWithMedia {
        getFirstMediaUrl as protected getFirstMediaUrlTrait;
    }

    protected $appends = [
        'url',
        'thumb',
        'icon',
        'formated_size'
    ];

    protected $hidden = [
        "responsive_images",
        "order_column",
        "created_at",
        "updated_at",
    ];

    public function getUrlAttribute(): string
    {
        return $this->getFullUrl();
    }

    public function getThumbAttribute(): string
    {
        if ($this->hasGeneratedConversion('thumb')) {
            return $this->getFirstMediaUrl('thumb');
        } else {
            return $this->getFullUrl();
        }
    }

    /**
     * to generate media url in case of fallback will
     * return the file type icon
     * @param string $conversion
     * @return string url
     */
    public function getFirstMediaUrl(string $conversion = ''): string
    {
        $url = $this->getUrl();
        $array = explode('.', $url);
        $extension = strtolower(end($array));
        if (in_array($extension, config('media-library.extensions_has_thumb'))) {
            return asset($this->getUrl($conversion));
        } else {
            return asset(config('media-library.icons_folder') . '/' . $extension . '.png');
        }
    }

    public function getIconAttribute(): string
    {
        if ($this->hasGeneratedConversion('icon')) {
            return $this->getFirstMediaUrl('icon');
        } else {
            return $this->getFullUrl();
        }
    }

    public function getFormatedSizeAttribute(): string
    {
        return formatedSize($this->size);
    }

    public function toArray(): array
    {
        if (!$this->hasGeneratedConversion('icon')) {
            parent::makeHidden('icon');
        }
        if (!$this->hasGeneratedConversion('thumb')) {
            parent::makeHidden('thumb');
        }
        parent::makeHidden(['model_type', 'model_id', 'collection_name', 'file_name', 'mime_type', 'disk','conversions_disk', 'size', 'manipulations', 'custom_properties','generated_conversions','uuid']);
        return parent::toArray();
    }
}

<?php
/*
 * File name: Uuids.php
 * Last modified: 2024.04.18 at 17:21:43
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Traits;

use Illuminate\Support\Str;

trait Uuids
{
    /**
     * Boot function from Laravel.
     */
    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Str::uuid()->toString();
            }
        });
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    /**
     * Get the auto-incrementing key type.
     *
     * @return string
     */
    public function getKeyType(): string
    {
        return 'string';
    }
}

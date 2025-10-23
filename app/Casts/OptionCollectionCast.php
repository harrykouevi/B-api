<?php
/*
 * File name: OptionCollectionCast.php
 * Last modified: 2024.04.18 at 17:53:30
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Casts;

use App\Models\Option;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Collection;
use JsonException;

/**
 * Class OptionCollectionCast
 * @package App\Casts
 */
class OptionCollectionCast implements CastsAttributes
{

    /**
     * @inheritDoc
     */
    public function get($model, string $key, $value, array $attributes): array
    {
        
        if(empty($value))  return [];
      
        $decodedValue = json_decode($value, true);
        return array_map(function ($value) {
            $option = Option::find($value['id']);
            if (!empty($option)) {
                return $option;
            }
            $option = new Option($value);
            $option->fillable[] = 'id';
            $option->id = $value['id'];
            return $option;
        }, $decodedValue);
        
        
    }

    /**
     * @inheritDoc
     * @throws JsonException
     */
    public function set($model, string $key, $value, array $attributes): array
    {
        
        $collection = $value instanceof Collection ? $value : collect($value);
        return [
            'options' => json_encode($collection->map->only(['id', 'name', 'price']), JSON_THROW_ON_ERROR)
        ];
    }
}

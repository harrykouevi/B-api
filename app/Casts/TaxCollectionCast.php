<?php
/*
 * File name: TaxCollectionCast.php
 * Last modified: 2024.04.18 at 17:53:30
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Casts;

use App\Models\Tax;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class TaxCollectionCast
 * @package App\Casts
 */
class TaxCollectionCast implements CastsAttributes
{

    /**
     * @inheritDoc
     */
    public function get($model, string $key, $value, array $attributes): array
    {
        if (empty($value) || $value === '[]' || $value === '') {
            return [];
        }
        
        if (!empty($value) ) {

            $decodedValue = is_string($value) ? json_decode($value, true) : $value;
            $taxesData =  ( array_keys($decodedValue) !== range(0, count($decodedValue) - 1) ) ? [$decodedValue] : $decodedValue;

            return collect($taxesData)->map(function ($item) {
                $tax = new Tax($item);
                $tax->fillable[] = 'id';
                $tax->id = $item['id'] ?? Null;
                return $tax;
            })->all();
            
        }
        return [];
                   
    }

    /**
     * @inheritDoc
     */
    public function set($model, string $key, $value, array $attributes): array
    {
      
        $decodedValue = is_string($value) ? json_decode($value, true) : $value;
        if( array_keys($decodedValue) !== range(0, count($decodedValue) - 1) ){ 
            $decodedValue['name'] = 'commission' ;
            $value =   [$decodedValue]  ;
        }else {
            $value =  $decodedValue ;
        };

        $collection = $value instanceof Collection ? $value : collect($value);
        return [
            $key => $collection->map(function ($item) {

                $array = $item instanceof Tax ? $item->toArray() : (array) $item;
           
                return collect($array)->only(['id', 'name', 'value', 'type']);
            })
            ->values() // facultatif, pour réindexer
            ->toJson()
        ];
    }
}

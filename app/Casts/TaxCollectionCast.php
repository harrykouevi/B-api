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
        if (!empty($value)) {
            $decodedValue = json_decode($value, true);
            return array_map(function ($value) {
                $tax = new Tax($value);
                $tax->fillable[] = 'id';
                $tax->id = $value['id'];
                return $tax;
            }, $decodedValue);
        }
        return [];
    }

    /**
     * @inheritDoc
     */
    public function set($model, string $key, $value, array $attributes): array
    {
//        if (!$value instanceof Collection) {
//            throw new InvalidArgumentException('The given value is not an Collection instance.');
//        }

        return [
            'taxes' => json_encode($value->map->only(['id', 'name', 'value', 'type']))
        ];
    }
}

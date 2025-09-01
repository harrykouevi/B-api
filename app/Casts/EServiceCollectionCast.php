<?php
/*
 * File name: EServiceCollectionCast.php
 * Last modified: 2024.04.18 at 17:53:30
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Casts;

use App\Models\EService;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Collection;

/**
 * Class EServiceCollectionCast
 * @package App\Casts
 */
class EServiceCollectionCast implements CastsAttributes
{

    /**
     * @inheritDoc
     */
    public function get($model, string $key, $value, array $attributes): array
    {
        if (!empty($value)) {
            $decodedValue = json_decode($value, true);
            return array_map(function ($value) {
                $eService = EService::find($value['id']);
                if (!empty($eService)) {
                    return $eService;
                }
                $eService = new EService($value);
                $eService->fillable[] = 'id';
                $eService->id = $value['id'];
                return $eService;
            }, $decodedValue);
        }
        return [];
    }

    /**
     * @inheritDoc
     */
    public function set($model, string $key, $value, array $attributes): array
    {
        $collection = $value instanceof Collection ? $value : collect($value);
        return [
            'e_services' => $collection->map(function ($item) {
                return collect($item)->only(['id', 'name', 'price', 'discount_price']);
            })
            ->values() // facultatif, pour réindexer
            ->toJson()
        ];
    }
}

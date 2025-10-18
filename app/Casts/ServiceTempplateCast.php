<?php
/*
 * File name: ServiceTempplateCast.php
 * Last modified: 2024.04.18 at 17:53:30
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Casts;

use App\Models\ServiceTemplate;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Class ServiceTempplateCast
 * @package App\Casts
 */
class ServiceTempplateCast implements CastsAttributes
{

    /**
     * @inheritDoc
     */
    public function get($model, string $key, $value, array $attributes): ServiceTemplate
    {
        $decodedValue = json_decode($value, true);
        $eService = ServiceTemplate::find($decodedValue['id']);
        // service exist in database
        if (!empty($eService)) {
            return $eService;
        }
        // if not exist the clone will load
        // create new service based on values stored on database
        $eService = new ServiceTemplate($decodedValue);
        // push id attribute fillable array
        $eService->fillable[] = 'id';
        // assign the id to service object
        $eService->id = $decodedValue['id'];
        return $eService;
    }

    /**
     * @inheritDoc
     */
    public function set($model, string $key, $value, array $attributes): array
    {
//        if (!$value instanceof ServiceTemplate) {
//            throw new InvalidArgumentException('The given value is not an ServiceTemplate instance.');
//        }

        return [
            'e_service' => json_encode(
                [
                    'id' => $value['id'],
                    'name' => $value['name'],
                    'description' => $value['description'],
                    
                ]
            )
        ];
    }
}

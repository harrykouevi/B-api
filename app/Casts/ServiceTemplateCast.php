<?php
/*
 * File name: ServiceTempplateCast.php
 * Last modified: 2025.11.03 at 10:53:30
 * Author: 
 */
namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use App\Models\ServiceTemplate;


class ServiceTemplateCast implements CastsAttributes
{
     /**
     * @inheritDoc
     */
    public function get($model, string $key, $value, array $attributes): ServiceTemplate
    {
        $decodedValue = json_decode($value, true);
        // $eService = ServiceTemplate::find($decodedValue['id']);
        // //service exist in database
        // if (!empty($eService)) {
        //     return $eService;
        // }

        // if not exist the clone will load
        // create new service based on values stored on database
        $eService = new ServiceTemplate($decodedValue);
        $eService->fillable[] = 'id';
        $eService->id = $decodedValue['id'];
        return $eService;
    }

    /**
     * @inheritDoc
     */
    public function set($model, string $key, $value, array $attributes): array
    {

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

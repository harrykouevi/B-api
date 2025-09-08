<?php

namespace App\Casts;

use App\Models\Booking;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class BookingCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_null($value)) {
            return new Booking() ;
            // return null; // ✅ retourne null si rien
        }

        // $decodedValue = json_decode($value, true);
        // $booking = Booking::find($decodedValue['id']);
        // // booking exist in database
        // if (!empty($booking)) {
        //     return $booking;
        // }
        
        $decoded = json_decode($value, true);
        $booking =  new Booking($decoded); 
        $booking->fillable[] = 'id';
        $booking->id = $decoded['id'];
        return $booking ;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value instanceof Booking) {
            return ['booking' => json_encode($value->toArray())];
        }

        return ['booking' => json_encode($value)];

        
    }
}

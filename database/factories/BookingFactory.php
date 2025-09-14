<?php
/*
 * File name: BookingFactory.php
 * Last modified: 2024.04.11 at 12:17:04
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Database\Factories;

use App\Models\Address;
use App\Models\Booking;
use App\Models\BookingStatus;
use App\Models\EService;
use App\Models\Salon;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{

    protected $model = Booking::class;

    /**
     * @throws \Exception
     */
    public function definition(): array
    {
        $salon = fake()->randomElement(Salon::where('accepted', '=', '1')->with('users')->get()->toArray());
        $eServices = EService::where('salon_id', '=', $salon['id'])->with('options')->limit(random_int(1, 3))->get();
        $userId = fake()->randomElement(['3', '5', '7']);
        $bookingStatus = BookingStatus::inRandomOrder()->first();
        $bookingAt = fake()->dateTimeBetween('-7 months', '70 hours');
        $startAt = fake()->dateTimeBetween('75 hours', '80 hours');
        $endsAt = fake()->dateTimeBetween('81 hours', '85 hours');
        return [
            'salon' => $salon,
            'e_services' => $eServices,
            'options' => $eServices->pluck('options')->flatten()->take(random_int(1, 3)),
            'quantity' => 1,
            'user_id' => $userId,
            'employee_id' => fake()->randomElement(array_column($salon['users'], 'id')),
            'booking_status_id' => $bookingStatus->id,
            'address' => fake()->randomElement(Address::where('user_id', '=', $userId)->get()->toArray()),
            'taxes' => Salon::find($salon['id'])->taxes,
            'booking_at' => $bookingAt,
            'start_at' => $bookingStatus->order >= 40 ? $startAt : null,
            'ends_at' => $bookingStatus->order >= 50 ? $endsAt : null,
            'hint' => fake()->sentence,
            'cancel' => fake()->boolean(5),
        ];
    }
}

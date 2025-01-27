<?php
/*
 * File name: PaymentsTableSeeder.php
 * Last modified: 2024.04.18 at 17:53:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */
namespace Database\Seeders;

use App\Models\Booking;
use DB;
use Illuminate\Database\Seeder;

class PaymentsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {

        DB::table('payments')->truncate();
        $bookings = Booking::all();
        foreach ($bookings as $booking) {
            DB::table('payments')->insert(array(
                'id' => $booking->id,
                'amount' => $booking->getTotal(),
                'description' => 'Booking ' . $booking->id,
                'user_id' => $booking->user_id,
                'payment_method_id' => 6,
                'payment_status_id' => in_array($booking->booking_status_id, [6,5,4]) ? 2 : 1,
                'updated_at' => $booking->booking_at,
                'created_at' => $booking->booking_at,
            ));
            $booking->payment_id = $booking->id;
            $booking->save();
        }
    }
}

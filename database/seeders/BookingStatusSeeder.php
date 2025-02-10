<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BookingStatus;
use Illuminate\Support\Facades\DB;


class BookingStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create BookingStatus records
        $bookingStatuses = [
            ['status' => 'Pending', 'order' => 1],
            ['status' => 'Confirmed', 'order' => 2],
            ['status' => 'Completed', 'order' => 3],
            ['status' => 'Cancelled', 'order' => 4],
            ['status' => 'No Show', 'order' => 5],
        ];

        DB::table('booking_statuses')->insert($bookingStatuses);
    }
}

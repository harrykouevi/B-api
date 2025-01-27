<?php
/*
 * File name: SlidesTableSeeder.php
 * Last modified: 2024.04.11 at 14:17:29
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Database\Seeders;

use App\Models\Slide;
use Illuminate\Database\Seeder;

class SlidesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {


        Slide::truncate();

        Slide::insert([
            [
                'id' => 1,
                'order' => 1,
                'text' => 'Assign a Handyman at Work to Fix the Household',
                'button' => 'Discover It',
                'text_position' => 'bottom_start',
                'text_color' => '#333333',
                'button_color' => '#D94464',
                'background_color' => '#FFFFFF',
                'indicator_color' => '#333333',
                'image_fit' => 'cover',
                'e_service_id' => NULL,
                'salon_id' => NULL,
                'enabled' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'order' => 2,
                'text' => 'Fix the Broken Stuff by Asking for the Technicians',
                'button' => 'Repair',
                'text_position' => 'bottom_start',
                'text_color' => '#333333',
                'button_color' => '#D94464',
                'background_color' => '#FFFFFF',
                'indicator_color' => '#333333',
                'image_fit' => 'cover',
                'e_service_id' => NULL,
                'salon_id' => NULL,
                'enabled' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'order' => 3,
                'text' => 'Add Hands to Your Cleaning Chores',
                'button' => 'Book Now',
                'text_position' => 'bottom_start',
                'text_color' => '#333333',
                'button_color' => '#D94464',
                'background_color' => '#FFFFFF',
                'indicator_color' => '#333333',
                'image_fit' => 'cover',
                'e_service_id' => NULL,
                'salon_id' => NULL,
                'enabled' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;



class UsersFinalRecoveryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::statement('
            INSERT INTO `users` (`id`, `name`, `email`, `phone_number`, `phone_verified_at`, `email_verified_at`, `password`, `api_token`, `device_token`, `remember_token`, `created_at`, `updated_at`, `sponsorship_at`, `sponsorship`, `otp`, `otp_expires_at`, `address`, `bio`) VALUES
            (239, "EUGÈNE CUTS", NULL, "+22871840739", NULL, NULL, '.str(Hash::make('CHARM1234')).', NULL, "", NULL, '.now().', '.now().', NULL, NULL, NULL, NULL, NULL, NULL),
            (245, "La’Klinik - Barber Shop", NULL, "+22890895555", NULL, NULL, '.str(Hash::make('CHARM1234')).', NULL, "", NULL, '.now().', '.now().', NULL, NULL, NULL, NULL, NULL, NULL),
            (248, "I GOT FRESH", NULL, "+22892353460", NULL, NULL, '.str(Hash::make('CHARM1234')).', NULL, "", NULL, '.now().', '.now().', NULL, NULL, NULL, NULL, NULL, NULL),
            (251, "VIVI COIFFURE", NULL, "+22899185571", NULL, NULL, '.str(Hash::make('CHARM1234')).', NULL, "", NULL, '.now().', '.now().', NULL, NULL, NULL, NULL, NULL, NULL),
            (252, "VIVI ONGLERIE", NULL, "+22893779967", NULL, NULL, '.str(Hash::make('CHARM1234')).', NULL, "", NULL, '.now().', '.now().', NULL, NULL, NULL, NULL, NULL, NULL),
            (253, "ANNA LUXURY", NULL, "+22890830775", NULL, NULL, '.str(Hash::make('CHARM1234')).', NULL, "", NULL, '.now().', '.now().', NULL, NULL, NULL, NULL, NULL, NULL),
            (254, "QUIST BARBER SHOP", NULL, "+22890962163", NULL, NULL, '.str(Hash::make('CHARM1234')).', NULL, "", NULL, '.now().', '.now().', NULL, NULL, NULL, NULL, NULL, NULL),
            (255, "GlowbyMilla", NULL, "+22893455119", NULL, NULL, '.str(Hash::make('CHARM1234')).', NULL, "", NULL, '.now().', '.now().', NULL, NULL, NULL, NULL, NULL, NULL);

            ');

             // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
    }
}

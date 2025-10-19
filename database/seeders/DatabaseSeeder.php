<?php
/*
 * File name: DatabaseSeeder.php
 * Last modified: 2024.04.18 at 17:53:53
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB ;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $this->call(RolesTableSeeder::class);
        $this->call(PermissionsTableSeeder::class); 
        $this->call(RoleHasPermissionsTableSeeder::class);
        // $this->call(AppSettingsTableSeeder::class);
        // $this->call(FaqCategoriesTableSeeder::class);
        // $this->call(CustomPagesTableSeeder::class);
        // $this->call(CurrenciesTableSeeder::class);
        // $this->call(FaqsTableSeeder::class);
        // $this->call(MediaTableSeeder::class);
        // $this->call(SlidesTableSeeder::class);
        // $this->call(BookingStatusesTableSeeder::class);
        // $this->call(PaymentMethodsTableSeeder::class);
        // $this->call(PaymentStatusesTableSeeder::class);
        // $this->call(UsersTableSeeder::class);
        // $this->call(WalletsTableSeeder::class);
        $this->call(AdminUserSeeder::class);
        

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}

<?php
/*
 * File name: ChangeCustomerRoleToSalon.php
 * Last modified: 2024.04.18 at 17:35:01
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Listeners;

/**
 * Class ChangeCustomerRoleToSalon
 * @package App\Listeners
 */
class ChangeCustomerRoleToSalon
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param object $event
     * @return void
     */
    public function handle(object $event): void
    {
        if ($event->newSalon->accepted) {
            foreach ($event->newSalon->users as $user) {
                $user->syncRoles(['salon owner']);
            }
        }
    }
}

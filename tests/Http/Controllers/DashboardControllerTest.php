<?php
/*
 * File name: DashboardControllerTest.php
 * Last modified: 2024.04.18 at 18:37:57
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Tests\Http\Controllers;

use Tests\Helpers\TestHelper;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{

    /**
     * @return void
     */
    public function testIndex(): void
    {
        $user = TestHelper::getAdmin();
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSeeTextInOrder([__('lang.dashboard'), __('lang.dashboard_overview'), __('lang.dashboard_more_info')]);
    }

}

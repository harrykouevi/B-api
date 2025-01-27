<?php
/*
 * File name: ModuleControllerTest.php
 * Last modified: 2024.04.18 at 18:53:44
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Tests\Http\Controllers;

use Tests\Helpers\TestHelper;
use Tests\TestCase;

class ModuleControllerTest extends TestCase
{

    /**
     * @return void
     */
    public function testIndex(): void
    {
        $user = TestHelper::getAdmin();
        $response = $this->actingAs($user)->get(route('modules.index'));
        $response->assertStatus(200);
        $response->assertSeeTextInOrder([__('lang.module_plural'), __('lang.module_desc'), __('lang.module_table')]);
    }

}

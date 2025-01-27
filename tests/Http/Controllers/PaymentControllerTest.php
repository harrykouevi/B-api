<?php
/*
 * File name: PaymentControllerTest.php
 * Last modified: 2024.04.11 at 14:51:04
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Tests\Http\Controllers;

use Tests\Helpers\TestHelper;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{

    /**
     * @return void
     */
    public function testIndex(): void
    {
        $user = TestHelper::getAdmin();
        $response = $this->actingAs($user)
            ->get(route('payments.index'));
        $response->assertStatus(200);
        $response->assertSeeTextInOrder([__('lang.payment_desc'), __('lang.payment_table'), __('lang.payment_amount')]);
        $response->assertDontSeeText(__('lang.payment_create'));
    }
}

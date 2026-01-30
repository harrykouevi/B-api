<?php
/*
 * File name: PaymentStatusControllerTest.php
 * Last modified: 2024.04.12 at 16:06:19
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Tests\Http\Controllers;

use App\Models\PaymentStatus;
use Tests\Helpers\TestHelper;
use Tests\TestCase;

class PaymentStatusControllerTest extends TestCase
{

    /**
     * @return void
     */
    public function testIndex(): void
    {
        $user = TestHelper::getAdmin();
        $response = $this->actingAs($user)
            ->get(route('paymentStatuses.index'));
        $response->assertSeeTextInOrder(["Payment Statuses", "Payments Statuses List"]);
    }

    /**
     * @return void
     */
    public function testEdit(): void
    {
        $user = TestHelper::getAdmin();
        $paymentStatusId = PaymentStatus::all()->random()->id;
        $response = $this->actingAs($user)
            ->get(route('paymentStatuses.edit', $paymentStatusId));
        $response->assertSeeTextInOrder(["Payment Statuses Management", "Status", "Order"]);
    }

    /**
     * Test Update PaymentStatus
     * @return void
     * @throws \JsonException
     * @throws \JsonException
     */
    public function testUpdate(): void
    {
        $user = TestHelper::getAdmin();
        $paymentStatus = PaymentStatus::factory()->make();
        $paymentStatusId = PaymentStatus::all()->random()->id;


        $response = $this->actingAs($user)
            ->put(route('paymentStatuses.update', $paymentStatusId), $paymentStatus->toTranslatableArray());
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas(PaymentStatus::getModel()->table, [
            'status' => TestHelper::getTranslatableColumn($paymentStatus->status),
        ]);
        $response->assertSessionHas('flash_notification.0.level', 'success');
        $response->assertSessionHas('flash_notification.0.message', __('lang.updated_successfully', ['operator' => __('lang.payment_status')]));
    }

    /**
     * @return void
     */
    public function testStatusFieldRequiredWhenUpdate(): void
    {
        $user = TestHelper::getAdmin();
        $paymentStatus = PaymentStatus::factory()->make();
        $paymentStatusId = PaymentStatus::all()->random()->id;

        $paymentStatus['status'] = null;

        $response = $this->actingAs($user)
            ->put(route('paymentStatuses.update', $paymentStatusId), $paymentStatus->toTranslatableArray());
        $response->assertSessionHasErrors("status", __('validation.required', ['attribute' => 'status']));
    }

    /**
     * @return void
     */
    public function testOrderFieldNumericWhenUpdate(): void
    {
        $user = TestHelper::getAdmin();
        $paymentStatus = PaymentStatus::factory()->make();
        $paymentStatusId = PaymentStatus::all()->random()->id;

        $paymentStatus['order'] = null;

        $response = $this->actingAs($user)
            ->put(route('paymentStatuses.update', $paymentStatusId), $paymentStatus->toTranslatableArray());
        $response->assertSessionHasErrors("order", __('validation.numeric', ['attribute' => 'order']));
    }

}

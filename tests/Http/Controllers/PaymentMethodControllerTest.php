<?php
/*
 * File name: PaymentMethodControllerTest.php
 * Last modified: 2024.04.12 at 16:06:19
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Tests\Http\Controllers;

use App\Models\PaymentMethod;
use Tests\Helpers\TestHelper;
use Tests\TestCase;

class PaymentMethodControllerTest extends TestCase
{

    /**
     * @return void
     */
    public function testIndex(): void
    {
        $user = TestHelper::getAdmin();
        $response = $this->actingAs($user)
            ->get(route('paymentMethods.index'));
        $response->assertStatus(200);
        $response->assertSeeTextInOrder([__('lang.payment_method_desc'), __('lang.payment_method_table'), __('lang.payment_method_create')]);
    }

    /**
     * @return void
     */
    public function testCreate(): void
    {
        $user = TestHelper::getAdmin();
        $response = $this->actingAs($user)
            ->get(route('paymentMethods.create'));
        $response->assertStatus(200);
        $response->assertSeeTextInOrder([__('lang.payment_method_desc'), __('lang.payment_method_name'), __('lang.payment_method_order')]);
    }

    /**
     * @return void
     */
    public function testEdit(): void
    {
        $user = TestHelper::getAdmin();
        $paymentMethodId = PaymentMethod::all()->random()->id;
        $response = $this->actingAs($user)
            ->get(route('paymentMethods.edit', $paymentMethodId));
        $response->assertStatus(200);
        $response->assertSeeTextInOrder([__('lang.payment_method_desc'), __('lang.payment_method_name'), __('lang.payment_method_order')]);
    }

    /**
     * @return void
     * @throws \JsonException
     * @throws \JsonException
     */
    public function testStore(): void
    {
        $user = TestHelper::getAdmin();
        $paymentMethod = PaymentMethod::factory()->make();
        $count = PaymentMethod::count();

        $response = $this->actingAs($user)
            ->post(route('paymentMethods.store'), $paymentMethod->toTranslatableArray());
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount(PaymentMethod::getModel()->table, $count + 1);
        $this->assertDatabaseHas(PaymentMethod::getModel()->table, [
            'name' => TestHelper::getTranslatableColumn($paymentMethod->name),
            'description' => TestHelper::getTranslatableColumn($paymentMethod->description),
            'route' => $paymentMethod->route
        ]);
    }

    /**
     * Test Update PaymentMethod
     * @return void
     * @throws \JsonException
     * @throws \JsonException
     */
    public function testUpdate(): void
    {
        $user = TestHelper::getAdmin();
        $paymentMethod = PaymentMethod::factory()->make();
        $paymentMethodId = PaymentMethod::all()->random()->id;


        $response = $this->actingAs($user)
            ->put(route('paymentMethods.update', $paymentMethodId), $paymentMethod->toTranslatableArray());
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas(PaymentMethod::getModel()->table, [
            'name' => TestHelper::getTranslatableColumn($paymentMethod->name),
            'description' => TestHelper::getTranslatableColumn($paymentMethod->description),
            'route' => $paymentMethod->route
        ]);
        $response->assertSessionHas('flash_notification.0.level', 'success');
        $response->assertSessionHas('flash_notification.0.message', __('lang.updated_successfully', ['operator' => __('lang.payment_method')]));
    }

    /**
     * @return void
     */
    public function testDestroy(): void
    {
        $user = TestHelper::getAdmin();
        $paymentMethodId = PaymentMethod::all()->random()->id;
        $response = $this->actingAs($user)
            ->delete(route('paymentMethods.destroy', $paymentMethodId));
        $response->assertRedirect(route('paymentMethods.index'));
        $response->assertSessionHas('flash_notification.0.level', 'success');
        $response->assertSessionHas('flash_notification.0.message', __('lang.deleted_successfully', ['operator' => __('lang.payment_method')]));
    }

    /**
     * @return void
     */
    public function testDestroyElementNotExist(): void
    {
        $user = TestHelper::getAdmin();
        $paymentMethodId = 50000; // not exist id
        $response = $this->actingAs($user)
            ->delete(route('paymentMethods.destroy', $paymentMethodId));
        $response->assertRedirect(route('paymentMethods.index'));
        $response->assertSessionHas('flash_notification.0.level', 'danger');
        $response->assertSessionHas('flash_notification.0.message', 'Payment Method not found');
    }

    /**
     * @return void
     */
    public function testRequiredFieldsWhenStore(): void
    {
        $user = TestHelper::getAdmin();
        $paymentMethod = PaymentMethod::factory()->make();

        $paymentMethod['name'] = null;
        $paymentMethod['description'] = null;
        $paymentMethod['route'] = null;
        $paymentMethod['order'] = null;

        $response = $this->actingAs($user)
            ->post(route('paymentMethods.store'), $paymentMethod->toTranslatableArray());
        $response->assertSessionHasErrors("name", __('validation.required', ['attribute' => 'name']));
        $response->assertSessionHasErrors("description", __('validation.required', ['attribute' => 'description']));
        $response->assertSessionHasErrors("route", __('validation.required', ['attribute' => 'route']));
        $response->assertSessionHasErrors("order", __('validation.numeric', ['attribute' => 'order']));
    }

    /**
     * @return void
     */
    public function testRequiredFieldsWhenUpdate(): void
    {
        $user = TestHelper::getAdmin();
        $paymentMethod = PaymentMethod::factory()->make();
        $paymentMethodId = PaymentMethod::all()->random()->id;

        $paymentMethod['name'] = null;
        $paymentMethod['description'] = null;
        $paymentMethod['route'] = null;
        $paymentMethod['order'] = null;


        $response = $this->actingAs($user)
            ->put(route('paymentMethods.update', $paymentMethodId), $paymentMethod->toTranslatableArray());
        $response->assertSessionHasErrors("name", __('validation.required', ['attribute' => 'name']));
        $response->assertSessionHasErrors("description", __('validation.required', ['attribute' => 'description']));
        $response->assertSessionHasErrors("route", __('validation.required', ['attribute' => 'route']));
        $response->assertSessionHasErrors("order", __('validation.numeric', ['attribute' => 'order']));
    }

    /**
     * @return void
     */
    public function testMaxCharactersFields(): void
    {
        $user = TestHelper::getAdmin();
        $paymentMethod = PaymentMethod::factory()->stateNameMore127Char()->stateDescriptionMore127Char()->stateRouteMore127Char()->stateOrderNegative()->make();

        $response = $this->actingAs($user)
            ->post(route('paymentMethods.store'), $paymentMethod->toTranslatableArray());
        $response->assertSessionHasErrors("name", __('validation.max.string', ['attribute' => 'name', 'max' => '127']));
        $response->assertSessionHasErrors("description", __('validation.max.string', ['attribute' => 'description', 'max' => '127']));
        $response->assertSessionHasErrors("route", __('validation.max.string', ['attribute' => 'route', 'max' => '127']));
        $response->assertSessionHasErrors("order", __('validation.min.numeric', ['attribute' => 'order']));
    }

}

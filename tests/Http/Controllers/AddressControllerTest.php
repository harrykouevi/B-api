<?php
/*
 * File name: AddressControllerTest.php
 * Last modified: 2024.04.11 at 11:52:47
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Tests\Http\Controllers;

use App\Models\Address;
use Tests\Helpers\TestHelper;
use Tests\TestCase;

class AddressControllerTest extends TestCase
{

    /**
     * @return void
     */
    public function testIndex(): void
    {
        $user = TestHelper::getAdmin();
        $response = $this->actingAs($user)
            ->get(route('addresses.index'));
        $response->assertStatus(200);
        $response->assertSeeTextInOrder([__('lang.address_desc'), __('lang.address_table'), __('lang.address_create')]);
    }

    /**
     * @return void
     */
    public function testCreate(): void
    {
        $user = TestHelper::getAdmin();
        $response = $this->actingAs($user)
            ->get(route('addresses.create'));
        $response->assertStatus(200);
        $response->assertSeeTextInOrder([__('lang.address_desc'), __('lang.address_description'), __('lang.address_address')]);
    }

    /**
     * @return void
     */
    public function testEdit(): void
    {
        $user = TestHelper::getAdmin();
        $addressId = Address::all()->random()->id;
        $response = $this->actingAs($user)
            ->get(route('addresses.edit', $addressId));
        $response->assertStatus(200);
        $response->assertSeeTextInOrder([__('lang.address_desc'), __('lang.address_description'), __('lang.address_address'), __('lang.address_latitude')]);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testStore(): void
    {
        $user = TestHelper::getAdmin();
        $address = Address::factory()->make();
        $count = Address::count();
        $response = $this->actingAs($user)->post(route('addresses.store'), $address->toArray());
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount(Address::getModel()->table, $count + 1);
        $this->assertDatabaseHas(Address::getModel()->table, [
            'description' => $address->description,
            'address' => $address->address,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
        ]);
        $response->assertSessionHas('flash_notification.0.level', 'success');
        $response->assertSessionHas('flash_notification.0.message', __('lang.saved_successfully', ['operator' => __('lang.address')]));
    }

    /**
     * Test Update Address
     * @return void
     * @throws \JsonException
     */
    public function testUpdate(): void
    {
        $user = TestHelper::getAdmin();
        $address = Address::factory()->make();
        $addressId = Address::all()->random()->id;


        $response = $this->actingAs($user)
            ->put(route('addresses.update', $addressId), $address->toArray());
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas(Address::getModel()->table, [
            'description' => $address->description,
            'address' => $address->address,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
        ]);
        $response->assertSessionHas('flash_notification.0.level', 'success');
        $response->assertSessionHas('flash_notification.0.message', __('lang.updated_successfully', ['operator' => __('lang.address')]));
    }

    /**
     * @return void
     */
    public function testDestroy(): void
    {
        $user = TestHelper::getAdmin();
        $addressId = Address::all()->random()->id;
        $response = $this->actingAs($user)
            ->delete(route('addresses.destroy', $addressId));
        $response->assertRedirect(route('addresses.index'));
        $this->assertDatabaseMissing(Address::getModel()->table, [
            'id' => $addressId,
        ]);
        $response->assertSessionHas('flash_notification.0.level', 'success');
        $response->assertSessionHas('flash_notification.0.message', __('lang.deleted_successfully', ['operator' => __('lang.address')]));
    }

    /**
     * @return void
     */
    public function testDestroyElementNotExist(): void
    {
        $user = TestHelper::getAdmin();
        $addressId = 50000; // not exist id
        $response = $this->actingAs($user)
            ->delete(route('addresses.destroy', $addressId));
        $response->assertRedirect(route('addresses.index'));
        $response->assertSessionHas('flash_notification.0.level', 'danger');
        $response->assertSessionHas('flash_notification.0.message', 'Address not found');
    }

    /**
     * @return void
     */
    public function testRequiredFieldsWhenStore(): void
    {
        $user = TestHelper::getAdmin();
        $address = Address::factory()->empty()->make();

        $response = $this->actingAs($user)
            ->post(route('addresses.store'), $address->toArray());
        $response->assertSessionHasErrors("address", __('validation.required', ['attribute' => 'address']));
        $response->assertSessionHasErrors("latitude", __('validation.required', ['attribute' => 'latitude']));
        $response->assertSessionHasErrors("longitude", __('validation.required', ['attribute' => 'longitude']));
    }

    /**
     * @return void
     */
    public function testRequiredFieldsWhenUpdate(): void
    {
        $user = TestHelper::getAdmin();
        $address = Address::factory()->empty()->make();
        $addressId = Address::all()->random()->id;

        $response = $this->actingAs($user)
            ->put(route('addresses.update', $addressId), $address->toArray());
        $response->assertSessionHasErrors("address", __('validation.required', ['attribute' => 'address']));
        $response->assertSessionHasErrors("latitude", __('validation.required', ['attribute' => 'latitude']));
        $response->assertSessionHasErrors("longitude", __('validation.required', ['attribute' => 'longitude']));
    }

    /**
     * @return void
     */
    public function testMaxCharactersFields(): void
    {
        $user = TestHelper::getAdmin();
        $address = Address::factory()->more_255_char()->make();
        $response = $this->actingAs($user)
            ->post(route('addresses.store'), $address->toArray());
        $response->assertSessionHasErrors("description", __('validation.max.string', ['attribute' => 'description', 'max' => '255']));
        $response->assertSessionHasErrors("address", __('validation.max.string', ['attribute' => 'address', 'max' => '255']));
        $response->assertSessionHasErrors("latitude", __('validation.max.numeric', ['attribute' => 'latitude', 'max' => '200']));
        $response->assertSessionHasErrors("longitude", __('validation.min.numeric', ['attribute' => 'longitude', 'min' => '-200']));
    }

}

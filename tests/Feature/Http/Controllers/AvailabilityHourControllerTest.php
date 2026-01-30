<?php
/*
 * File name: AvailabilityHourControllerTest.php
 * Last modified: 2024.04.12 at 16:03:18
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Tests\Http\Controllers;

use App\Models\AvailabilityHour;
use Tests\Helpers\TestHelper;
use Tests\TestCase;

class AvailabilityHourControllerTest extends TestCase
{

    /**
     * @return void
     */
    public function testIndex(): void
    {
        $user = TestHelper::getAdmin();
        $response = $this->actingAs($user)
            ->get(route('availabilityHours.index'));
        $response->assertStatus(200);
        $response->assertSeeTextInOrder([__('lang.availability_hour_desc'), __('lang.availability_hour_table'), __('lang.availability_hour_create')]);
    }

    /**
     * @return void
     */
    public function testCreate(): void
    {
        $user = TestHelper::getAdmin();
        $response = $this->actingAs($user)
            ->get(route('availabilityHours.create'));
        $response->assertStatus(200);
        $response->assertSeeTextInOrder([__('lang.availability_hour_desc'), __('lang.availability_hour_day'), __('lang.availability_hour_start_at'), __('lang.availability_hour_end_at')]);
    }

    /**
     * @return void
     */
    public function testEdit(): void
    {
        $user = TestHelper::getAdmin();
        $availabilityHourId = AvailabilityHour::all()->random()->id;
        $response = $this->actingAs($user)
            ->get(route('availabilityHours.edit', $availabilityHourId));
        $response->assertStatus(200);
        $response->assertSeeTextInOrder([__('lang.availability_hour_desc'), __('lang.availability_hour_day'), __('lang.availability_hour_data')]);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testStore(): void
    {
        $user = TestHelper::getAdmin();
        $availabilityHour = AvailabilityHour::factory()->make();
        $count = AvailabilityHour::count();

        $response = $this->actingAs($user)
            ->post(route('availabilityHours.store'), $availabilityHour->toTranslatableArray());
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount(AvailabilityHour::getModel()->table, $count + 1);
        $this->assertDatabaseHas(AvailabilityHour::getModel()->table, [
            'day' => $availabilityHour->day,
            'start_at' => $availabilityHour->start_at,
            'end_at' => $availabilityHour->end_at,
            'data' => TestHelper::getTranslatableColumn($availabilityHour->data),
            'salon_id' => $availabilityHour->salon_id
        ]);
        $response->assertSessionHas('flash_notification.0.level', 'success');
        $response->assertSessionHas('flash_notification.0.message', __('lang.saved_successfully', ['operator' => __('lang.availability_hour')]));
    }

    /**
     * Test Update AvailabilityHour
     * @return void
     * @throws \JsonException
     */
    public function testUpdate(): void
    {
        $user = TestHelper::getAdmin();
        $availabilityHour = AvailabilityHour::factory()->make();
        $availabilityHourId = AvailabilityHour::all()->random()->id;

        $response = $this->actingAs($user)
            ->put(route('availabilityHours.update', $availabilityHourId), $availabilityHour->toTranslatableArray());
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas(AvailabilityHour::getModel()->table, [
            'day' => $availabilityHour->day,
            'start_at' => $availabilityHour->start_at,
            'end_at' => $availabilityHour->end_at,
            'data' => TestHelper::getTranslatableColumn($availabilityHour->data),
            'salon_id' => $availabilityHour->salon_id
        ]);
        $response->assertSessionHas('flash_notification.0.level', 'success');
        $response->assertSessionHas('flash_notification.0.message', __('lang.updated_successfully', ['operator' => __('lang.availability_hour')]));
    }

    /**
     * @return void
     */
    public function testDestroy(): void
    {
        $user = TestHelper::getAdmin();
        $availabilityHourId = AvailabilityHour::all()->random()->id;
        $response = $this->actingAs($user)
            ->delete(route('availabilityHours.destroy', $availabilityHourId));
        $response->assertRedirect(route('availabilityHours.index'));
        $this->assertDatabaseMissing(AvailabilityHour::getModel()->table, [
            'id' => $availabilityHourId,
        ]);
        $response->assertSessionHas('flash_notification.0.level', 'success');
        $response->assertSessionHas('flash_notification.0.message', __('lang.deleted_successfully', ['operator' => __('lang.availability_hour')]));
    }

    /**
     * @return void
     */
    public function testDestroyElementNotExist(): void
    {
        $user = TestHelper::getAdmin();
        $availabilityHourId = 50000; // not exist id
        $response = $this->actingAs($user)
            ->delete(route('availabilityHours.destroy', $availabilityHourId));
        $response->assertRedirect(route('availabilityHours.index'));
        $response->assertSessionHas('flash_notification.0.level', 'danger');
        $response->assertSessionHas('flash_notification.0.message', 'Availability Hour not found');
    }

    /**
     * @return void
     */
    public function testRequiredFieldsWhenStore(): void
    {
        $user = TestHelper::getAdmin();
        $availabilityHour = AvailabilityHour::factory()->make();

        $availabilityHour['day'] = null;
        $availabilityHour['start_at'] = null;
        $availabilityHour['end_at'] = null;
        $availabilityHour['salon_id'] = null;

        $response = $this->actingAs($user)
            ->post(route('availabilityHours.store'), $availabilityHour->toTranslatableArray());
        $response->assertSessionHasErrors("day", __('validation.required', ['attribute' => 'day']));
        $response->assertSessionHasErrors("start_at", __('validation.required', ['attribute' => 'start_at']));
        $response->assertSessionHasErrors("end_at", __('validation.required', ['attribute' => 'end_at']));
        $response->assertSessionHasErrors("salon_id", __('validation.required', ['attribute' => 'salon_id']));
    }

    /**
     * @return void
     */
    public function testRequiredFieldsWhenUpdate(): void
    {
        $user = TestHelper::getAdmin();
        $availabilityHour = AvailabilityHour::factory()->make();
        $availabilityHourId = AvailabilityHour::all()->random()->id;

        $availabilityHour['day'] = null;
        $availabilityHour['start_at'] = null;
        $availabilityHour['end_at'] = null;
        $availabilityHour['salon_id'] = null;


        $response = $this->actingAs($user)
            ->put(route('availabilityHours.update', $availabilityHourId), $availabilityHour->toTranslatableArray());
        $response->assertSessionHasErrors("day", __('validation.required', ['attribute' => 'day']));
        $response->assertSessionHasErrors("start_at", __('validation.required', ['attribute' => 'start_at']));
        $response->assertSessionHasErrors("end_at", __('validation.required', ['attribute' => 'end_at']));
        $response->assertSessionHasErrors("salon_id", __('validation.required', ['attribute' => 'salon_id']));
    }

    /**
     * @return void
     */
    public function testMaxCharactersFields(): void
    {
        $user = TestHelper::getAdmin();
        $availabilityHour = AvailabilityHour::factory()->day_more_16_char()->end_at_lest_start_at()->not_exist_salon_id()->make();
        $response = $this->actingAs($user)
            ->post(route('availabilityHours.store'), $availabilityHour->toTranslatableArray());
        $response->assertSessionHasErrors("day", __('validation.max.string', ['attribute' => 'day', 'max' => '16']));
        $response->assertSessionHasErrors("end_at", __('validation.after', ['attribute' => 'end_at', 'date' => 'start_at']));
        $response->assertSessionHasErrors("salon_id", __('validation.exists', ['attribute' => 'salon_id']));
    }

}

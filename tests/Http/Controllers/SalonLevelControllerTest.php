<?php
/*
 * File name: SalonLevelControllerTest.php
 * Last modified: 2024.04.12 at 16:06:19
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Tests\Http\Controllers;

use App\Models\SalonLevel;
use Tests\Helpers\TestHelper;
use Tests\TestCase;

class SalonLevelControllerTest extends TestCase
{

    /**
     * @return void
     */
    public function testIndex(): void
    {
        $user = TestHelper::getAdmin();
        $response = $this->actingAs($user)
            ->get(route('salonLevels.index'));
        $response->assertStatus(200);
        $response->assertSeeTextInOrder([__('lang.salon_level_desc'), __('lang.salon_level_table'), __('lang.salon_level_create')]);
    }

    /**
     * @return void
     */
    public function testCreate(): void
    {
        $user = TestHelper::getAdmin();
        $response = $this->actingAs($user)
            ->get(route('salonLevels.create'));
        $response->assertStatus(200);
        $response->assertSeeTextInOrder([__('lang.salon_level_desc'), __('lang.salon_level_name'), __('lang.salon_level_commission')]);
    }

    /**
     * @return void
     */
    public function testEdit(): void
    {
        $user = TestHelper::getAdmin();
        $salonTypeId = SalonLevel::all()->random()->id;
        $response = $this->actingAs($user)
            ->get(route('salonLevels.edit', $salonTypeId));
        $response->assertStatus(200);
        $response->assertSeeTextInOrder([__('lang.salon_level_desc'), __('lang.salon_level_name'), __('lang.salon_level_commission')]);
    }

    /**
     * @return void
     * @throws \JsonException
     * @throws \JsonException
     */
    public function testStore(): void
    {
        $user = TestHelper::getAdmin();
        $salonType = SalonLevel::factory()->make();
        $count = SalonLevel::count();

        $response = $this->actingAs($user)
            ->post(route('salonLevels.store'), $salonType->toTranslatableArray());
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount(SalonLevel::getModel()->table, $count + 1);
        $this->assertDatabaseHas(SalonLevel::getModel()->table, [
            'name' => TestHelper::getTranslatableColumn($salonType->name),
            'commission' => $salonType->commission,
            'disabled' => $salonType->disabled
        ]);
        $response->assertSessionHas('flash_notification.0.level', 'success');
        $response->assertSessionHas('flash_notification.0.message', __('lang.saved_successfully', ['operator' => __('lang.salon_level')]));
    }

    /**
     * Test Update SalonLevel
     * @return void
     * @throws \JsonException
     * @throws \JsonException
     */
    public function testUpdate(): void
    {
        $user = TestHelper::getAdmin();
        $salonType = SalonLevel::factory()->make();
        $salonTypeId = SalonLevel::all()->random()->id;


        $response = $this->actingAs($user)
            ->put(route('salonLevels.update', $salonTypeId), $salonType->toTranslatableArray());
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas(SalonLevel::getModel()->table, [
            'name'=> TestHelper::getTranslatableColumn($salonType->name),
            'commission' => $salonType->commission,
            'disabled' => $salonType->disabled
        ]);
        $response->assertSessionHas('flash_notification.0.level', 'success');
        $response->assertSessionHas('flash_notification.0.message', __('lang.updated_successfully', ['operator' => __('lang.salon_level')]));
    }

    /**
     * @return void
     */
    public function testDestroy(): void
    {
        $user = TestHelper::getAdmin();
        $salonTypeId = SalonLevel::all()->random()->id;
        $response = $this->actingAs($user)
            ->delete(route('salonLevels.destroy', $salonTypeId));
        $response->assertRedirect(route('salonLevels.index'));
        $this->assertDatabaseMissing(SalonLevel::getModel()->table, [
            'id' => $salonTypeId,
        ]);
        $response->assertSessionHas('flash_notification.0.level', 'success');
        $response->assertSessionHas('flash_notification.0.message', __('lang.deleted_successfully', ['operator' => __('lang.salon_level')]));
    }

    /**
     * @return void
     */
    public function testDestroyElementNotExist(): void
    {
        $user = TestHelper::getAdmin();
        $salonTypeId = 50000; // not exist id
        $response = $this->actingAs($user)
            ->delete(route('salonLevels.destroy', $salonTypeId));
        $response->assertRedirect(route('salonLevels.index'));
        $response->assertSessionHas('flash_notification.0.level', 'danger');
        $response->assertSessionHas('flash_notification.0.message', 'E Provider Type not found');
    }

    /**
     * @return void
     */
    public function testRequiredFieldsWhenStore(): void
    {
        $user = TestHelper::getAdmin();
        $salonType = SalonLevel::factory()->make();

        $salonType['name'] = null;
        $salonType['commission'] = null;

        $response = $this->actingAs($user)
            ->post(route('salonLevels.store'), $salonType->toTranslatableArray());
        $response->assertSessionHasErrors("name", __('validation.required', ['attribute' => 'name']));
        $response->assertSessionHasErrors("commission", __('validation.numeric', ['attribute' => 'commission']));
    }

    /**
     * @return void
     */
    public function testRequiredFieldsWhenUpdate(): void
    {
        $user = TestHelper::getAdmin();
        $salonType = SalonLevel::factory()->make();
        $salonTypeId = SalonLevel::all()->random()->id;

        $salonType['name'] = null;
        $salonType['commission'] = null;


        $response = $this->actingAs($user)
            ->put(route('salonLevels.update', $salonTypeId), $salonType->toTranslatableArray());
        $response->assertSessionHasErrors("name", __('validation.required', ['attribute' => 'name']));
        $response->assertSessionHasErrors("commission", __('validation.numeric', ['attribute' => 'commission']));
    }

    /**
     * @return void
     */
    public function testMaxCharactersFields(): void
    {
        $user = TestHelper::getAdmin();
        $salonType = SalonLevel::factory()->stateNameMore127Char()->stateCommissionMore100()->make();
        $response = $this->actingAs($user)
            ->post(route('salonLevels.store'), $salonType->toTranslatableArray());
        $response->assertSessionHasErrors("name", __('validation.max.string', ['attribute' => 'name', 'max' => '127']));
        $response->assertSessionHasErrors("commission", __('validation.max.numeric', ['attribute' => 'commission']));
    }

    /**
     * @return void
     */
    public function testMinCommissionField(): void
    {
        $user = TestHelper::getAdmin();
        $salonType = SalonLevel::factory()->stateCommissionLess0()->make();
        $response = $this->actingAs($user)
            ->post(route('salonLevels.store'), $salonType->toTranslatableArray());
        $response->assertSessionHasErrors("commission", __('validation.min.numeric', ['attribute' => 'commission']));
    }

}

<?php
/*
 * File name: AwardControllerTest.php
 * Last modified: 2024.04.12 at 16:03:50
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Tests\Http\Controllers;

use App\Models\Award;
use Tests\Helpers\TestHelper;
use Tests\TestCase;

class AwardControllerTest extends TestCase
{

    /**
     * @return void
     */
    public function testIndex(): void
    {
        $user = TestHelper::getAdmin();
        $response = $this->actingAs($user)
            ->get(route('awards.index'));
        $response->assertStatus(200);
        $response->assertSeeTextInOrder([__('lang.award_desc'), __('lang.award_table'), __('lang.award_create')]);
    }

    /**
     * @return void
     */
    public function testCreate(): void
    {
        $user = TestHelper::getAdmin();
        $response = $this->actingAs($user)
            ->get(route('awards.create'));
        $response->assertStatus(200);
        $response->assertSeeTextInOrder([__('lang.award_desc'), __('lang.award_title'), __('lang.award_description')]);
    }

    /**
     * @return void
     */
    public function testEdit(): void
    {
        $user = TestHelper::getAdmin();
        $awardId = Award::all()->random()->id;
        $response = $this->actingAs($user)
            ->get(route('awards.edit', $awardId));
        $response->assertStatus(200);
        $response->assertSeeTextInOrder([__('lang.award_desc'), __('lang.award_title'), __('lang.award_description')]);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testStore(): void
    {
        $user = TestHelper::getAdmin();
        $award = Award::factory()->make();
        $count = Award::count();
        $response = $this->actingAs($user)->post(route('awards.store'), $award->toTranslatableArray());
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount(Award::getModel()->table, $count + 1);
        $this->assertDatabaseHas(Award::getModel()->table, [
            'title' => TestHelper::getTranslatableColumn($award->title),
            'description' => TestHelper::getTranslatableColumn($award->description),
            'salon_id' => $award->salon_id
        ]);
        $response->assertSessionHas('flash_notification.0.level', 'success');
        $response->assertSessionHas('flash_notification.0.message', __('lang.saved_successfully', ['operator' => __('lang.award')]));
    }

    /**
     * Test Update Award
     * @return void
     * @throws \JsonException
     * @throws \JsonException
     */
    public function testUpdate(): void
    {
        $user = TestHelper::getAdmin();
        $award = Award::factory()->make();
        $awardId = Award::all()->random()->id;


        $response = $this->actingAs($user)
            ->put(route('awards.update', $awardId), $award->toTranslatableArray());
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas(Award::getModel()->table, [
            'title' => TestHelper::getTranslatableColumn($award->title),
            'description' => TestHelper::getTranslatableColumn($award->description),
            'salon_id' => $award->salon_id
        ]);
        $response->assertSessionHas('flash_notification.0.level', 'success');
        $response->assertSessionHas('flash_notification.0.message', __('lang.updated_successfully', ['operator' => __('lang.award')]));
    }

    /**
     * @return void
     */
    public function testDestroy(): void
    {
        $user = TestHelper::getAdmin();
        $awardId = Award::all()->random()->id;
        $response = $this->actingAs($user)
            ->delete(route('awards.destroy', $awardId));
        $response->assertRedirect(route('awards.index'));
        $this->assertDatabaseMissing(Award::getModel()->table, [
            'id' => $awardId,
        ]);
        $response->assertSessionHas('flash_notification.0.level', 'success');
        $response->assertSessionHas('flash_notification.0.message', __('lang.deleted_successfully', ['operator' => __('lang.award')]));
    }

    /**
     * @return void
     */
    public function testDestroyElementNotExist(): void
    {
        $user = TestHelper::getAdmin();
        $awardId = 50000; // not exist id
        $response = $this->actingAs($user)
            ->delete(route('awards.destroy', $awardId));
        $response->assertRedirect(route('awards.index'));
        $response->assertSessionHas('flash_notification.0.level', 'danger');
        $response->assertSessionHas('flash_notification.0.message', 'Award not found');
    }

    /**
     * @return void
     */
    public function testRequiredFieldsWhenStore(): void
    {
        $user = TestHelper::getAdmin();
        $award = Award::factory()->make();

        $award['title'] = null;
        $award['salon_id'] = null;

        $response = $this->actingAs($user)
            ->post(route('awards.store'), $award->toTranslatableArray());
        $response->assertSessionHasErrors("title", __('validation.required', ['attribute' => 'title']));
        $response->assertSessionHasErrors("salon_id", __('validation.required', ['attribute' => 'salon_id']));
    }

    /**
     * @return void
     */
    public function testRequiredFieldsWhenUpdate(): void
    {
        $user = TestHelper::getAdmin();
        $award = Award::factory()->make();
        $awardId = Award::all()->random()->id;

        $award['title'] = null;
        $award['salon_id'] = null;


        $response = $this->actingAs($user)
            ->put(route('awards.update', $awardId), $award->toTranslatableArray());
        $response->assertSessionHasErrors("title", __('validation.required', ['attribute' => 'title']));
        $response->assertSessionHasErrors("salon_id", __('validation.required', ['attribute' => 'salon_id']));
    }

    /**
     * @return void
     */
    public function testMaxCharactersFields(): void
    {
        $user = TestHelper::getAdmin();
        $award = Award::factory()->titleMore127Char()->notExistSalonId()->make();
        $response = $this->actingAs($user)
            ->post(route('awards.store'), $award->toTranslatableArray());
        $response->assertSessionHasErrors("title", __('validation.max.string', ['attribute' => 'title', 'max' => '127']));
        $response->assertSessionHasErrors("salon_id", __('validation.exists', ['attribute' => 'salon_id']));
    }

}

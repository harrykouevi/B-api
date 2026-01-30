<?php
/*
 * File name: ExperienceControllerTest.php
 * Last modified: 2024.04.12 at 16:06:19
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Tests\Http\Controllers;

use App\Models\Experience;
use Tests\Helpers\TestHelper;
use Tests\TestCase;

class ExperienceControllerTest extends TestCase
{

    /**
     * @return void
     */
    public function testIndex(): void
    {
        $user = TestHelper::getAdmin();
        $response = $this->actingAs($user)
            ->get(route('experiences.index'));
        $response->assertStatus(200);
        $response->assertSeeTextInOrder([__('lang.experience_desc'), __('lang.experience_table'), __('lang.experience_create')]);
    }

    /**
     * @return void
     */
    public function testShow(): void
    {
        $user = TestHelper::getAdmin();
        $experience = Experience::all()->random();
        $response = $this->actingAs($user)
            ->get(route('experiences.show', $experience->id));
        $response->assertStatus(200);
        $response->assertSeeTextInOrder([$experience->title, $experience->description], false);
    }

    /**
     * @return void
     */
    public function testCreate(): void
    {
        $user = TestHelper::getAdmin();
        $response = $this->actingAs($user)
            ->get(route('experiences.create'));
        $response->assertStatus(200);
        $response->assertSeeTextInOrder([__('lang.experience_desc'), __('lang.experience_title'), __('lang.experience_description')]);
    }

    /**
     * @return void
     */
    public function testEdit(): void
    {
        $user = TestHelper::getAdmin();
        $experienceId = Experience::all()->random()->id;
        $response = $this->actingAs($user)
            ->get(route('experiences.edit', $experienceId));
        $response->assertStatus(200);
        $response->assertSeeTextInOrder([__('lang.experience_desc'), __('lang.experience_title'), __('lang.experience_description')]);
    }

    /**
     * @return void
     * @throws \JsonException
     * @throws \JsonException
     */
    public function testStore(): void
    {
        $user = TestHelper::getAdmin();
        $experience = Experience::factory()->make();
        $count = Experience::count();

        $response = $this->actingAs($user)
            ->post(route('experiences.store'), $experience->toTranslatableArray());
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount(Experience::getModel()->table, $count + 1);
        $this->assertDatabaseHas(Experience::getModel()->table, [
            'title' => TestHelper::getTranslatableColumn($experience->title),
            'description' => TestHelper::getTranslatableColumn($experience->description),
            'salon_id' => $experience->salon_id
        ]);
        $response->assertSessionHas('flash_notification.0.level', 'success');
        $response->assertSessionHas('flash_notification.0.message', __('lang.saved_successfully', ['operator' => __('lang.experience')]));
    }

    /**
     * Test Update Experience
     * @return void
     * @throws \JsonException
     * @throws \JsonException
     */
    public function testUpdate(): void
    {
        $user = TestHelper::getAdmin();
        $experience = Experience::factory()->make();
        $experienceId = Experience::all()->random()->id;


        $response = $this->actingAs($user)
            ->put(route('experiences.update', $experienceId), $experience->toTranslatableArray());
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas(Experience::getModel()->table, [
            'title' => TestHelper::getTranslatableColumn($experience->title),
            'description' => TestHelper::getTranslatableColumn($experience->description),
            'salon_id' => $experience->salon_id
        ]);
        $response->assertSessionHas('flash_notification.0.level', 'success');
        $response->assertSessionHas('flash_notification.0.message', __('lang.updated_successfully', ['operator' => __('lang.experience')]));
    }

    /**
     * @return void
     */
    public function testDestroy(): void
    {
        $user = TestHelper::getAdmin();
        $experienceId = Experience::all()->random()->id;
        $response = $this->actingAs($user)
            ->delete(route('experiences.destroy', $experienceId));
        $response->assertRedirect(route('experiences.index'));
        $this->assertDatabaseMissing(Experience::getModel()->table, [
            'id' => $experienceId,
        ]);
        $response->assertSessionHas('flash_notification.0.level', 'success');
        $response->assertSessionHas('flash_notification.0.message', __('lang.deleted_successfully', ['operator' => __('lang.experience')]));
    }

    /**
     * @return void
     */
    public function testDestroyElementNotExist(): void
    {
        $user = TestHelper::getAdmin();
        $experienceId = 50000; // not exist id
        $response = $this->actingAs($user)
            ->delete(route('experiences.destroy', $experienceId));
        $response->assertRedirect(route('experiences.index'));
        $response->assertSessionHas('flash_notification.0.level', 'danger');
        $response->assertSessionHas('flash_notification.0.message', 'Experience not found');
    }

    /**
     * @return void
     */
    public function testRequiredFieldsWhenStore(): void
    {
        $user = TestHelper::getAdmin();
        $experience = Experience::factory()->make();

        $experience['title'] = null;
        $experience['salon_id'] = null;

        $response = $this->actingAs($user)
            ->post(route('experiences.store'), $experience->toTranslatableArray());
        $response->assertSessionHasErrors("title", __('validation.required', ['attribute' => 'title']));
        $response->assertSessionHasErrors("salon_id", __('validation.required', ['attribute' => 'salon_id']));
    }

    /**
     * @return void
     */
    public function testRequiredFieldsWhenUpdate(): void
    {
        $user = TestHelper::getAdmin();
        $experience = Experience::factory()->make();
        $experienceId = Experience::all()->random()->id;

        $experience['title'] = null;
        $experience['salon_id'] = null;


        $response = $this->actingAs($user)
            ->put(route('experiences.update', $experienceId), $experience->toTranslatableArray());
        $response->assertSessionHasErrors("title", __('validation.required', ['attribute' => 'title']));
        $response->assertSessionHasErrors("salon_id", __('validation.required', ['attribute' => 'salon_id']));
    }

    /**
     * @return void
     */
    public function testMaxCharactersFields(): void
    {
        $user = TestHelper::getAdmin();
        $experience = Experience::factory()->titleMore127Char()->notExistSalonId()->make();
        $response = $this->actingAs($user)
            ->post(route('experiences.store'), $experience->toTranslatableArray());
        $response->assertSessionHasErrors("title", __('validation.max.string', ['attribute' => 'title', 'max' => '127']));
        $response->assertSessionHasErrors("salon_id", __('validation.exists', ['attribute' => 'salon_id']));
    }

}

<?php
/*
 * File name: FaqControllerTest.php
 * Last modified: 2024.04.12 at 16:06:19
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Tests\Http\Controllers;

use App\Models\Faq;
use Tests\Helpers\TestHelper;
use Tests\TestCase;

class FaqControllerTest extends TestCase
{

    /**
     * @return void
     */
    public function testIndex(): void
    {
        $user = TestHelper::getAdmin();
        $response = $this->actingAs($user)
            ->get(route('faqs.index'));
        $response->assertSeeTextInOrder(["Faqs Management", "Faqs List", "Create Faq"]);
    }

    /**
     * @return void
     */
    public function testCreate(): void
    {
        $user = TestHelper::getAdmin();
        $response = $this->actingAs($user)
            ->get(route('faqs.create'));
        $response->assertSeeTextInOrder(["Faqs Management", "Question", "Answer", "Faq Category"]);
    }

    /**
     * @return void
     */
    public function testEdit(): void
    {
        $user = TestHelper::getAdmin();
        $faqId = Faq::all()->random()->id;
        $response = $this->actingAs($user)
            ->get(route('faqs.edit', $faqId));
        $response->assertSeeTextInOrder(["Faqs Management", "Question", "Answer", "Faq Category"]);
    }

    /**
     * @return void
     * @throws \JsonException
     * @throws \JsonException
     */
    public function testStore(): void
    {
        $user = TestHelper::getAdmin();
        $faq = Faq::factory()->make();

        $response = $this->actingAs($user)
            ->post(route('faqs.store'), $faq->toTranslatableArray());
        $response->assertSessionHasNoErrors();
    }

    /**
     * Test Update Faq
     * @return void
     * @throws \JsonException
     * @throws \JsonException
     */
    public function testUpdate(): void
    {
        $user = TestHelper::getAdmin();
        $faq = Faq::factory()->make();
        $faqId = Faq::all()->random()->id;

        $response = $this->actingAs($user)
            ->put(route('faqs.update', $faqId), $faq->toTranslatableArray());
        $response->assertSessionHasNoErrors();
    }

    /**
     * @return void
     */
    public function testDestroy(): void
    {
        $user = TestHelper::getAdmin();
        $faqId = Faq::all()->random()->id;
        $response = $this->actingAs($user)
            ->delete(route('faqs.destroy', $faqId));
        $response->assertRedirect(route('faqs.index'));
        $this->assertDatabaseMissing(Faq::getModel()->table, [
            'id' => $faqId,
        ]);
        $response->assertSessionHas('flash_notification.0.level', 'success');
        $response->assertSessionHas('flash_notification.0.message', __('lang.deleted_successfully', ['operator' => __('lang.faq')]));
    }

    /**
     * @return void
     */
    public function testQuestionFieldRequired(): void
    {
        $user = TestHelper::getAdmin();
        $faq = Faq::factory()->make();

        $faq['question'] = null;

        $response = $this->actingAs($user)
            ->post(route('faqs.store'), $faq->toTranslatableArray());
        $response->assertSessionHasErrors("question");
    }

    /**
     * @return void
     */
    public function testAnswerFieldRequired(): void
    {
        $user = TestHelper::getAdmin();
        $faq = Faq::factory()->make();

        $faq['answer'] = null;

        $response = $this->actingAs($user)
            ->post(route('faqs.store'), $faq->toTranslatableArray());
        $response->assertSessionHasErrors("answer");
    }

    /**
     * @return void
     */
    public function testCategoryFieldRequired(): void
    {
        $user = TestHelper::getAdmin();

        $faq = Faq::factory()->make();
        $faq['faq_category_id'] = null;

        $response = $this->actingAs($user)
            ->post(route('faqs.store'), $faq->toTranslatableArray());
        $response->assertSessionHasErrors("faq_category_id");
    }

}

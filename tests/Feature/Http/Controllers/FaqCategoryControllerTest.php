<?php
/*
 * File name: FaqCategoryControllerTest.php
 * Last modified: 2024.04.12 at 16:06:19
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Tests\Http\Controllers;


use App\Models\FaqCategory;
use Tests\Helpers\TestHelper;
use Tests\TestCase;

class FaqCategoryControllerTest extends TestCase
{
    /**
     * Test Index Faq Categories
     * @return void
     */
    public function testIndex(): void
    {
        $user = TestHelper::getAdmin();
        $response = $this->actingAs($user)
            ->get(route('faqCategories.index'));
        $response->assertStatus(200);
        $response->assertSeeTextInOrder(["Faq Categories Management", "Faq Categories List", "Create Faq Category"]);
    }

    /**
     * Test Create Faq Categories
     * @return void
     */
    public function testCreate(): void
    {
        $user = TestHelper::getAdmin();
        $response = $this->actingAs($user)
            ->get(route('faqCategories.create'));
        $response->assertStatus(200);
        $response->assertSeeTextInOrder(["Faq Categories Management", "Name"]);
    }

    /**
     * Test Edit FaqCategory
     * @return void
     */
    public function testEdit(): void
    {
        $user = TestHelper::getAdmin();
        $faqCategoryId = FaqCategory::all()->random()->id;
        $response = $this->actingAs($user)
            ->get(route('faqCategories.edit', $faqCategoryId));
        $response->assertStatus(200);
        $response->assertSeeTextInOrder(["Faq Categories Management", "Name"]);
    }

    /**
     * Test Store FaqCategory
     * @return void
     * @throws \JsonException
     * @throws \JsonException
     */
    public function testStore(): void
    {
        $user = TestHelper::getAdmin();
        $faqCategory = FaqCategory::factory()->make();
        $response = $this->actingAs($user)
            ->post(route('faqCategories.store'), $faqCategory->toTranslatableArray());
        $response->assertSessionHasNoErrors();
    }

    /**
     * Test Store FaqCategory NameFieldRequired
     * @return void
     */
    public function testNameFieldRequired(): void
    {
        $user = TestHelper::getAdmin();
        $faqCategory = FaqCategory::factory()->make();
        $faqCategory['name'] = '';
        $response = $this->actingAs($user)
            ->post(route('faqCategories.store'), $faqCategory->toTranslatableArray());
        $response->assertSessionHasErrors('name');
    }

    /**
     * Test Store FaqCategory testNameLessThan127
     * @return void
     */
    public function testNameLessThan127Character(): void
    {
        $user = TestHelper::getAdmin();
        $faqCategory = FaqCategory::factory()->make();
        $faqCategory['name'] = "Atque aspernatur eum occaecati corporis deleniti laborum. In quo debitis dolores repudiandae. Ex et vel quod nemo ipsam quaerat. Dolores aut deserunt omnis. Ipsam est iusto consequatur aut. Ex et vel quod nemo ipsam quaerat. Dolores aut deserunt omnis. Ipsam est iusto consequatur aut. Ex et vel quod nemo ipsam quaerat. Dolores aut deserunt omnis. Ipsam est iusto consequatur aut. Ex et vel quod nemo ipsam quaerat. Dolores aut deserunt omnis. Ipsam est iusto consequatur aut. Ex et vel quod nemo ipsam quaerat. Dolores aut deserunt omnis. Ipsam est iusto consequatur aut. Ex et vel quod nemo ipsam quaerat. Dolores aut deserunt omnis. Ipsam est iusto consequatur aut.";
        $response = $this->actingAs($user)
            ->post(route('faqCategories.store'), $faqCategory->toTranslatableArray());
        $response->assertSessionHasErrors('name');
    }

    /**
     * Test Update FaqCategory
     * @return void
     * @throws \JsonException
     * @throws \JsonException
     */
    public function testUpdate(): void
    {
        $user = TestHelper::getAdmin();
        $faqCategory = FaqCategory::factory()->make();
        $faqCategory['name'] = "New Service";
        $faqCategoryId = FaqCategory::all()->random()->id;

        $response = $this->actingAs($user)
            ->put(route('faqCategories.update', $faqCategoryId), $faqCategory->toTranslatableArray());
        $response->assertSessionHasNoErrors();
    }

}

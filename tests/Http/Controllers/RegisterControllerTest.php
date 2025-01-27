<?php
/*
 * File name: RegisterControllerTest.php
 * Last modified: 2024.04.11 at 15:29:52
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Tests\Http\Controllers;

use App\Models\User;
use Tests\TestCase;

class RegisterControllerTest extends TestCase
{
    /**
     * @return void
     */
    public function testIndex(): void
    {
        $response = $this->get(route('register'));
        $response->assertSeeTextInOrder(["Register a new membership", "Sign In"]);
    }

    /**
     * @throws \JsonException
     */
    public function testRegister()
    {
        $user = User::factory()->stateRegister()->make();
        $response = $this->post('/register', [
            "email" => $user->email,
            "name" => $user->name,
            "password" => $user->password,
            "password_confirmation" => $user->password_confirmation,
        ]);
        $response->assertSessionHasNoErrors();
    }
}

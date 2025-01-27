<?php
/*
 * File name: LoginControllerTest.php
 * Last modified: 2024.04.11 at 14:51:04
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Tests\Http\Controllers;

use App\Models\User;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    /**
     * @return void
     */
    public function testIndex(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertSeeTextInOrder(["Sign in to start your session", "Login"]);
    }

    /**
     * @throws \JsonException
     */
    public function testLogin()
    {
        $user = User::all()->random();

        $response = $this->post('/login', [
            "email" => $user["email"],
            "password" => "123456"
        ]);
        $response->assertSessionHasNoErrors();
        $response = $this->actingAs($user)->get(route('users.profile'));
        $response->assertDontSeeText("403");
        $response->assertSeeTextInOrder(["About Me", $user['email'], "Save User"]);
    }
}

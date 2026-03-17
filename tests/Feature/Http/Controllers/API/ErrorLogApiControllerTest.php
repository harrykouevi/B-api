<?php

namespace Tests\Feature\Http\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ErrorLogApiControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_error(): void
    {
        try{ 
            Log::spy(); // 👈 permet de vérifier que le log est appelé

            $response = $this->postJson(route('api.logs.store'), [
                'message' => 'Erreur sms Flutter',
                'stacktrace' => '[firebase_auth/too-many-requests] We have blocked all requests from this device due to unusual activity. Try again later.

                                    #0      MethodChannelFirebaseAuth.verifyPhoneNumber (package:firebase_auth_platform_interface/src/method_channel/method_channel_firebase_auth.dart:612:7)
                                    <asynchronous suspension>
                                    #1      FirebaseAuth.verifyPhoneNumber (package:firebase_auth/src/firebase_auth.dart:598:31)
                                    <asynchronous suspension>
                                    #2      AuthService.sendOtp (package:my_app/services/auth_service.dart:45:5)
                                    <asynchronous suspension>
                                    #3      LoginController.requestOtp (package:my_app/controllers/login_controller.dart:88:7)
                                    <asynchronous suspension>
                                    #4      _LoginScreenState._onSendCodePressed (package:my_app/screens/login_screen.dart:132:9)
                                    <asynchronous suspension>',
                'device' => 'android',
            ]);

            
        
            $response->assertStatus(200)
                    ->assertJson([
                         'data' => [
                            'status' => 'error logged'
                        ]
                    ]);

          
        } catch (\Exception $e) {
            Log::error('FAIL:'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);

            throw $e; 
        }
    }
}

<?php


use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'api_name' => 'Barber Management API',
        'version' => '1.0',
        'description' => 'This API allows you to manage barbers, including creating, updating, and deleting barber records.',
        'endpoints' => [ ]
    ]);
});

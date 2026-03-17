<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class ErrorLogApiController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'stacktrace' => 'nullable|string',
            'device' => 'nullable|string',
        ]);

       

    // Crée un logger simple
    $logger = new Logger('flutter');
    $logger->pushHandler(new StreamHandler(storage_path('logs/flutter.log'), Logger::DEBUG));
    $logger->error('Erreur Flutter', [
            'message' => $request->message,
            'stacktrace' => $request->stacktrace,
            'device' => $request->device,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now()->toDateTimeString(),
        ]);

        return $this->sendResponse([
            'status' => 'error logged'
        ], 'error logged');

    }
}

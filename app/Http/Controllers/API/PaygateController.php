<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\PaygateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaygateController extends Controller
{
    private PaygateService $paygateService;

    public function __construct(PaygateService $paygateService)
    {
        $this->paygateService = $paygateService;
    }

    public function handleCallback(Request $request)
    {
        Log::info("Requête de callback Paygate reçue", [
            'request_data' => $request->all()
        ]);

        $this->paygateService->handleReturnUrl($request);

        return response()->json(['status' => 'ok']);
    }
}

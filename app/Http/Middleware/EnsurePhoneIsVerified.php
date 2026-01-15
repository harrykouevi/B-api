<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Middleware to ensure user's phone number is verified
 *
 * Usage:
 * Route::middleware(['auth:api', 'verified.phone'])->group(function () {
 *     // Protected routes here
 * });
 */
class EnsurePhoneIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // If user is authenticated
        if ($user) {
            // Check if phone is verified
            if (is_null($user->phone_verified_at)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Veuillez vérifier votre numéro de téléphone avant de continuer.',
                    'error_code' => 'PHONE_NOT_VERIFIED',
                    'data' => [
                        'phone_number' => $user->phone_number,
                        'verified' => false
                    ]
                ], 403);
            }
        }

        return $next($request);
    }
}

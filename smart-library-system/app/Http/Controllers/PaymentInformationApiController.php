<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PaymentInformationService;
use Illuminate\Http\JsonResponse;

class PaymentInformationApiController extends Controller
{
    public function show(
        User $user,
        PaymentInformationService $paymentInformationService
    ): JsonResponse {
        return response()->json([
            'success' => true,

            'data' =>
                $paymentInformationService
                    ->getUserPaymentStatus($user->id),
        ]);
    }
}
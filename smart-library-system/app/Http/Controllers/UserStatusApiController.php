<?php

namespace App\Http\Controllers;

use App\Models\Borrowing;
use App\Models\User;
use App\Services\BorrowingService;
use App\Services\UserAccountService;
use Illuminate\Http\JsonResponse;

class UserStatusApiController extends Controller
{
    public function show(
        User $user,
        BorrowingService $borrowingService,
        UserAccountService $userAccountService
    ): JsonResponse {
        // Detect borrowings whose due date has already passed.
        $borrowingService->markOverdueBorrowings($user->id);

        // Update the student's account status.
        $userAccountService->syncAccountStatus($user);

        $user->refresh();

        $hasUnresolvedFine = Borrowing::query()
            ->where('user_id', $user->id)
            ->unresolvedOverdue()
            ->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $user->id,
                'account_status' => $user->account_status,
                'has_unresolved_fine' => $hasUnresolvedFine,
            ],
        ]);
    }
}
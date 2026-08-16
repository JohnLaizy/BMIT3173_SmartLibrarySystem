<?php

namespace App\Http\Controllers;

use App\Exceptions\BorrowingRuleViolation;
use App\Http\Requests\CompleteSimulatedPaymentRequest;
use App\Http\Requests\StartSimulatedPaymentRequest;
use App\Models\Borrowing;
use App\Models\User;
use App\Services\Payments\SimulatedPaymentGatewayResolver;
use App\Services\SimulatedPaymentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Throwable;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->authenticatedUser($request);

        Gate::authorize('viewAny', Borrowing::class);

        $paymentsQuery = Borrowing::query()
            ->with([
                'book',
                'student',
                'paymentApprover',
            ])
            ->where('overdue_fee_cents', '>', 0)
            ->latest('updated_at');

        if ($user->isStudent()) {
            $paymentsQuery->where('user_id', $user->id);
        }

        return view('payments.index', [
            // Payment History 也遵守每页五笔记录的列表规则。
            'payments' => $paymentsQuery->paginate(5),
        ]);
    }

    public function show(
        Request $request,
        Borrowing $borrowing,
        SimulatedPaymentGatewayResolver $gatewayResolver
    ): View {
        $this->authenticatedUser($request);

        Gate::authorize('view', $borrowing);

        $borrowing->load([
            'book',
            'student',
            'paymentApprover',
            'paymentAudits.actor',
        ]);

        return view('payments.show', [
            'borrowing' => $borrowing,
            'gateways' => $gatewayResolver->all(),
            'selectedGateway' => $borrowing->payment_method === null
                ? null
                : $gatewayResolver->resolve(
                    $borrowing->payment_method
                ),
        ]);
    }

    /**
     * Display an internal receipt after a librarian has approved the
     * simulated payment. This is deliberately not represented as a bank
     * receipt, because no bank transaction is verified by this system.
     */
    public function receipt(
        Request $request,
        Borrowing $borrowing
    ): View {
        $this->authenticatedUser($request);

        Gate::authorize('view', $borrowing);

        abort_unless(
            $borrowing->status === Borrowing::STATUS_COMPLETED
                && $borrowing->payment_reference !== null,
            404
        );

        $borrowing->load([
            'book',
            'student',
            'paymentApprover',
        ]);

        return view('payments.receipt', [
            'borrowing' => $borrowing,
        ]);
    }

    public function start(
        StartSimulatedPaymentRequest $request,
        Borrowing $borrowing,
        SimulatedPaymentService $paymentService
    ): RedirectResponse {
        $student = $this->authenticatedUser($request);

        Gate::authorize('submitPayment', $borrowing);

        try {
            $paymentService->start(
                $student,
                $borrowing,
                $request->string('payment_method')->toString()
            );

            return to_route('payments.show', $borrowing)->with(
                'success',
                'Payment reference generated. Continue with the simulated bank step.'
            );
        } catch (BorrowingRuleViolation $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function complete(
        CompleteSimulatedPaymentRequest $request,
        Borrowing $borrowing,
        SimulatedPaymentService $paymentService
    ): RedirectResponse {
        $student = $this->authenticatedUser($request);

        Gate::authorize('submitPayment', $borrowing);

        try {
            $paymentService->complete($student, $borrowing);

            return to_route('payments.index')->with(
                'success',
                'Simulated payment submitted for librarian approval.'
            );
        } catch (BorrowingRuleViolation $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (Throwable) {
            return back()->with(
                'error',
                'Unable to submit the payment simulation. Please try again.'
            );
        }
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new AuthorizationException;
        }

        return $user;
    }
}

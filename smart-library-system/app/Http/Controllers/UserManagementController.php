<?php

namespace App\Http\Controllers;

use App\Facades\UserManagementFacade;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    use ProfileValidationRules;
    /**
     * Display all users for librarian management.
     */



    public function index(
    Request $request,
    UserManagementFacade $facade
): View {
    abort_unless(auth()->user()?->isLibrarian(), 403);

    $validatedSearch = $request->validate([
        'search' => [
            'nullable',
            'string',
            'max:100',
        ],
    ]);

    $search = trim($validatedSearch['search'] ?? '');

    $users = $facade->searchUsers($search);

    return view(
        'users.index',
        compact('users', 'search')
    );
}



    /**
     * Show the edit form for a user.
     */
    public function edit(User $user): View
    {
        abort_unless(auth()->user()?->isLibrarian(), 403);

        return view('users.edit', compact('user'));
    }

    /**
     * Update a user's account information.
     */


    public function update(
    Request $request,
    User $user,
    UserManagementFacade $facade
    ): RedirectResponse
    {
        abort_unless(auth()->user()?->isLibrarian(), 403);

        $isOwnAccount = auth()->id() === $user->id;

        $validated = $request->validate([
            'name' => $this->nameRules(),

            'email' => $this->emailRules($user->id),

            'phone' => $this->phoneRules(),

            'role' => [
                'required',
                Rule::in([
                    User::ROLE_STUDENT,
                    User::ROLE_LIBRARIAN,
                ]),
            ],
            'account_status' => [
                'required',
                Rule::in([
                    User::STATUS_ACTIVE,
                    User::STATUS_INACTIVE,
                ]),
            ],
        ]);

        // Prevent librarians from removing their own librarian access
        // or deactivating their own account.
        if ($isOwnAccount) {
            $validated['role'] = User::ROLE_LIBRARIAN;
            $validated['account_status'] = User::STATUS_ACTIVE;
        }

        $facade->updateUser(
            $user,
            $validated,
            $isOwnAccount
        );

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully.');
    }
}
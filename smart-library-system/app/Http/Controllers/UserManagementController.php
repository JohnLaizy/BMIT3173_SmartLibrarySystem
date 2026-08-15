<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    /**
     * Display all users for librarian management.
     */
    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->isLibrarian(), 403);

        $search = trim((string) $request->query('search', ''));
 
        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('users.index', compact('users', 'search'));
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
    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless(auth()->user()?->isLibrarian(), 403);

        $isOwnAccount = auth()->id() === $user->id;

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
            ],
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

        $user->update($validated);

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully.');
    }
}
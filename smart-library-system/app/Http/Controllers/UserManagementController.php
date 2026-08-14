<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    /**
     * Display all users for librarian management.
     */
    public function index(): View
    {
        $users = User::orderBy('name')->paginate(10);

        return view('users.index', compact('users'));
    }
}
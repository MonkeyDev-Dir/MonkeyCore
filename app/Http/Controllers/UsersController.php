<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UsersController extends Controller
{
    public function __construct(private UserService $userService) {}

    public function index(): View
    {
        return view('pages.users', [
            'users' => $this->userService->all(),
        ]);
    }

    public function edit(User $user): View
    {
        return view('pages.users-edit', [
            'user' => $user,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->userService->save($request->validated(), $user);

        return to_route('users.edit', $user)->with('success', __('Usuario actualizado correctamente.'));
    }
}

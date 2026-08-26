<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private UserService $userService) {}

    public function index(): View
    {
        return view('pages.profile', [
            'user' => auth()->user(),
        ]);
    }

    public function regenerateAvatar(): RedirectResponse
    {
        $user = request()->user();

        abort_unless($user instanceof User, 403);

        $this->userService->regenerateAvatar($user);

        return to_route('profile')->with('success', __('Avatar actualizado correctamente.'));
    }
}

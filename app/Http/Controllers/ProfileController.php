<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View
    {
        return view('pages.profile', [
            'user' => auth()->user(),
        ]);
    }
}

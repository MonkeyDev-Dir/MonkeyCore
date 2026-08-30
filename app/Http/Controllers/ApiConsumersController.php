<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ApiConsumersController extends Controller
{
    public function index(): View
    {
        return view('pages.api-consumers');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class WorkItemsController extends Controller
{
    public function index(): View
    {
        return view('pages.work-items');
    }
}

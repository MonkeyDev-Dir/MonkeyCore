<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class CivilConsultationController extends Controller
{
    public function index(): View
    {
        return view('pages.civil-consultation');
    }
}

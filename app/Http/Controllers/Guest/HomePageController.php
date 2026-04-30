<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;

class HomePageController extends Controller
{
    public function homePage()
    {
        return inertia('guest/homepage/Index');
    }
}
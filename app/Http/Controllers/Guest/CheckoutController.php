<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function index()
    {
        return inertia('guest/sales/Checkout');
    }

    public function store(Request $request)
    {
        dd($request);
    }
}
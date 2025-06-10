<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Display the admin dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        // In a real application, you might fetch data for the dashboard here
        // For example:
        // $newOrders = Order::where('status', 'pending')->count();
        // $totalProducts = Product::count();
        // return view('admin.dashboard', compact('newOrders', 'totalProducts'));

        return view('admin.dashboard');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $thisMonthOrders = Order::where('created_at', '>=', Carbon::now()->startOfMonth())->count();
        $totalRevenue = Order::where('status', 'completed')->sum('total_amount');
        $pendingOrders = Order::where('status', 'pending')->count();
        $completedOrders = Order::where('status', 'completed')->count();
        $activeProducts = Product::where('status', 'published')
            ->whereNull('deleted_at')->count();
        $totalUsers = User::count();

        return view('dashboard.index', compact(
            'thisMonthOrders',
            'totalRevenue',
            'pendingOrders',
            'completedOrders',
            'activeProducts',
            'totalUsers'
        ));
    }
}

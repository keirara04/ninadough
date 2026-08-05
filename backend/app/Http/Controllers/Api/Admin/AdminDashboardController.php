<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PreorderDate;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $today = now()->toDateString();

        return response()->json(['data' => [
            'todays_order_count' => Order::whereDate('created_at', $today)->count(),
            'pending_payment_review_count' => Order::where('status', 'payment_submitted')->count(),
            'revenue_sen' => [
                'today' => (int) Order::where('payment_status', 'paid')->whereDate('paid_at', $today)->sum('total_sen'),
                'this_week' => (int) Order::where('payment_status', 'paid')->where('paid_at', '>=', now()->startOfWeek())->sum('total_sen'),
                'all_time' => (int) Order::where('payment_status', 'paid')->sum('total_sen'),
            ],
            'upcoming_preorder_dates' => PreorderDate::query()
                ->whereDate('order_date', '>=', $today)
                ->orderBy('order_date')
                ->limit(7)
                ->get()
                ->map(fn (PreorderDate $date) => [
                    'order_date' => $date->order_date->toDateString(),
                    'capacity_used' => $date->reserved_capacity,
                    'capacity_limit' => $date->capacity_limit,
                    'status' => $date->status,
                ]),
            'orders_needing_attention' => Order::where('status', 'awaiting_payment')
                ->where('created_at', '<=', now()->subHour())
                ->count(),
        ]]);
    }
}

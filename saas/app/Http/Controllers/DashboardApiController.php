<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Template;
use Illuminate\Support\Carbon;

class DashboardApiController extends Controller
{
    public function stats()
    {
        return response()->json([
            'websites_total' => Website::count(),
            'websites_today' => Website::whereDate('created_at', Carbon::today())->count(),
            'users_total' => User::count(),
            'revenue_total' => Transaction::where('status', 'paid')->sum('amount'),
        ]);
    }

    public function websites()
    {
        return response()->json([
            'status_distribution' => [
                'draft' => Website::where('status', 'draft')->count(),
                'live' => Website::where('status', 'live')->count(),
            ],
            'recent' => Website::latest()->limit(10)->get(['id', 'business_name', 'status', 'created_at']),
        ]);
    }

    public function revenue()
    {
        return response()->json([
            'monthly' => Transaction::where('status', 'paid')
                ->whereMonth('created_at', Carbon::now()->month)
                ->sum('amount'),
            'today' => Transaction::where('status', 'paid')
                ->whereDate('created_at', Carbon::today())
                ->sum('amount'),
        ]);
    }

    public function templates()
    {
        return response()->json(
            Template::withCount('websites')->get(['id', 'name', 'slug', 'websites_count'])
        );
    }

    public function users()
    {
        return response()->json([
            'total' => User::count(),
            'recent' => User::latest()->limit(5)->get(['id', 'name', 'email', 'created_at']),
        ]);
    }
}

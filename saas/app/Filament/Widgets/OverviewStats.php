<?php

namespace App\Filament\Widgets;

use App\Models\Website;
use App\Models\Transaction;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class OverviewStats extends BaseWidget
{
    protected function getStats(): array
    {
        $todaySites = Website::whereDate('created_at', Carbon::today())->count();
        $totalSites = Website::count();
        $liveSites = Website::where('status', 'live')->count();
        $paidUsers = Transaction::where('status', 'paid')->distinct('user_id')->count();
        $totalUsers = User::count();
        
        $conversionRate = $totalUsers > 0 ? round(($paidUsers / $totalUsers) * 100, 1) : 0;
        
        $totalRevenue = Transaction::where('status', 'paid')->sum('amount');
        $todayRevenue = Transaction::where('status', 'paid')->whereDate('created_at', Carbon::today())->sum('amount');

        return [
            Stat::make('Total Websites', $totalSites)
                ->description($todaySites . ' generated today')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 2, 10, 3, 15, 4, $todaySites])
                ->color('success'),
                
            Stat::make('Active Websites', $liveSites)
                ->description('Currently live')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('primary'),

            Stat::make('Total Revenue', '₹' . number_format($totalRevenue))
                ->description('₹' . number_format($todayRevenue) . ' today')
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart([1000, 3000, 2000, 5000, 4500, 6000, $todayRevenue])
                ->color('success'),

            Stat::make('Conversion Rate', $conversionRate . '%')
                ->description('Paid user percentage')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),
        ];
    }
}

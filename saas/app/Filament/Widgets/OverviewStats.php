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
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $now        = Carbon::now();
        $today      = Carbon::today();
        $yesterday  = Carbon::yesterday();
        $thisMonth  = Carbon::now()->startOfMonth();
        $lastMonth  = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        // ── Websites ────────────────────────────────────────────────────────
        $totalSites    = Website::count();
        $liveSites     = Website::where('status', 'live')->count();
        $todaySites    = Website::whereDate('created_at', $today)->count();
        $yesterdaySites = Website::whereDate('created_at', $yesterday)->count();
        $sitesThisWeek = Website::where('created_at', '>=', $now->copy()->subDays(7))->count();

        // Last 7 days chart data
        $sitesDailyChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $sitesDailyChart[] = Website::whereDate('created_at', $now->copy()->subDays($i))->count();
        }

        // ── Revenue ─────────────────────────────────────────────────────────
        $totalRevenue   = Transaction::where('status', 'paid')->sum('amount');
        $todayRevenue   = Transaction::where('status', 'paid')->whereDate('created_at', $today)->sum('amount');
        $thisMonthRev   = Transaction::where('status', 'paid')->where('created_at', '>=', $thisMonth)->sum('amount');
        $lastMonthRev   = Transaction::where('status', 'paid')->whereBetween('created_at', [$lastMonth, $lastMonthEnd])->sum('amount');
        $revGrowth      = $lastMonthRev > 0 ? round((($thisMonthRev - $lastMonthRev) / $lastMonthRev) * 100, 1) : 0;

        $revDailyChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $revDailyChart[] = (float) Transaction::where('status', 'paid')
                ->whereDate('created_at', $now->copy()->subDays($i))
                ->sum('amount');
        }

        // ── Users ────────────────────────────────────────────────────────────
        $totalUsers    = User::count();
        $newThisWeek   = User::where('created_at', '>=', $now->copy()->subDays(7))->count();
        $paidUsers     = Transaction::where('status', 'paid')->distinct('user_id')->count('user_id');
        $convRate      = $totalUsers > 0 ? round(($paidUsers / $totalUsers) * 100, 1) : 0;

        // ── Transactions MRR ─────────────────────────────────────────────────
        $pendingCount  = Transaction::where('status', 'pending')->count();
        $failedCount   = Transaction::where('status', 'failed')->count();

        $siteTrend = $yesterdaySites > 0
            ? ($todaySites > $yesterdaySites ? 'up' : 'down')
            : 'up';

        return [
            Stat::make('Total Websites', number_format($totalSites))
                ->description($todaySites . ' today · ' . $sitesThisWeek . ' this week')
                ->descriptionIcon($siteTrend === 'up' ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($sitesDailyChart)
                ->color($siteTrend === 'up' ? 'success' : 'warning'),

            Stat::make('Live Sites', number_format($liveSites))
                ->description(number_format($totalSites - $liveSites) . ' inactive · ' . $liveSites . ' published')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('primary'),

            Stat::make('Total Revenue', '₹' . number_format($totalRevenue))
                ->description('₹' . number_format($thisMonthRev) . ' this month · ' . ($revGrowth >= 0 ? '+' : '') . $revGrowth . '% MoM')
                ->descriptionIcon($revGrowth >= 0 ? 'heroicon-m-banknotes' : 'heroicon-m-arrow-trending-down')
                ->chart($revDailyChart)
                ->color($revGrowth >= 0 ? 'success' : 'danger'),

            Stat::make('Total Users', number_format($totalUsers))
                ->description($newThisWeek . ' new this week · ' . $paidUsers . ' paid')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('Paid Users', number_format($paidUsers))
                ->description("Conversion rate: {$convRate}%")
                ->descriptionIcon('heroicon-m-currency-rupee')
                ->color('success'),

            Stat::make('Pending / Failed', $pendingCount . ' / ' . $failedCount)
                ->description('Transactions needing attention')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($pendingCount + $failedCount > 0 ? 'warning' : 'success'),
        ];
    }
}

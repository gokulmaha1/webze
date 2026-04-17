<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue Tracking';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 1;
    public ?string $filter = 'week';

    protected function getFilters(): ?array
    {
        return [
            'week' => 'Last week',
            'month' => 'Last month',
            'year' => 'This year',
        ];
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;

        $labels = [];
        $data = [];

        if ($activeFilter === 'week') {
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $labels[] = $date->format('D');
                $sum = Transaction::where('status', 'paid')->whereDate('created_at', $date)->sum('amount');
                $data[] = $sum;
            }
        } else if ($activeFilter === 'month') {
            for ($i = 4; $i >= 0; $i--) {
                $date = Carbon::today()->subWeeks($i);
                $labels[] = 'Week ' . $date->weekOfMonth;
                $sum = Transaction::where('status', 'paid')
                    ->whereBetween('created_at', [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()])
                    ->sum('amount');
                $data[] = $sum;
            }
        } else {
            $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
            $data = [0, 0, 0, 1500, 4800, Transaction::whereMonth('created_at', Carbon::now()->month)->sum('amount')];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (₹)',
                    'data' => $data,
                    'backgroundColor' => '#10b981',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\Website;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class WebsiteTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Websites Generated Over Time';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 1;
    public ?string $filter = 'week';

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Today',
            'week' => 'Last week',
            'month' => 'Last month',
            'year' => 'This year',
        ];
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;

        // Dummy aggregate logic - replace with Flowframe/Trend if installed
        $labels = [];
        $data = [];

        if ($activeFilter === 'week') {
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $labels[] = $date->format('D');
                $data[] = Website::whereDate('created_at', $date)->count();
            }
        } else if ($activeFilter === 'month') {
            for ($i = 4; $i >= 0; $i--) {
                $date = Carbon::today()->subWeeks($i);
                $labels[] = 'Week ' . $date->weekOfMonth;
                $data[] = Website::whereBetween('created_at', [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()])->count();
            }
        } else {
            $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
            $data = [5, 12, 18, 30, 42, Website::whereMonth('created_at', Carbon::now()->month)->count()];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Websites Created',
                    'data' => $data,
                    'borderColor' => '#0ea5e9',
                    'backgroundColor' => 'rgba(14, 165, 233, 0.2)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\Website;
use App\Models\Transaction;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class CategoryBreakdownChart extends ChartWidget
{
    protected static ?string $heading = 'Top Business Categories';
    protected static ?string $description = 'Distribution of websites by business category';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $categories = Website::selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->orderByDesc('count')
            ->limit(8)
            ->pluck('count', 'category')
            ->toArray();

        if (empty($categories)) {
            return [
                'datasets' => [[
                    'label' => 'Websites',
                    'data'  => [1],
                    'backgroundColor' => ['#e2e8f0'],
                ]],
                'labels' => ['No Data Yet'],
            ];
        }

        $palette = [
            '#6366f1', '#f43f5e', '#10b981', '#f59e0b',
            '#3b82f6', '#a855f7', '#14b8a6', '#f97316',
        ];

        return [
            'datasets' => [[
                'label'           => 'Websites',
                'data'            => array_values($categories),
                'backgroundColor' => array_slice($palette, 0, count($categories)),
                'borderWidth'     => 0,
                'hoverOffset'     => 8,
            ]],
            'labels' => array_keys($categories),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}

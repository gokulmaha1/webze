<?php

namespace App\Filament\Widgets;

use App\Models\Template;
use App\Models\Website;
use Filament\Widgets\ChartWidget;

class TemplateDistributionChart extends ChartWidget
{
    protected static ?string $heading = 'Template Distribution';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $templates = Template::withCount('websites')->get();
        
        $labels = [];
        $data = [];
        $colors = ['#f43f5e', '#a855f7', '#3b82f6', '#10b981', '#f59e0b', '#64748b', '#14b8a6', '#f97316', '#6366f1'];

        foreach ($templates as $template) {
            if ($template->websites_count > 0) {
                $labels[] = $template->name;
                $data[] = $template->websites_count;
            }
        }

        // Handle case where no websites have templates yet
        if (empty($labels)) {
            $labels = ['No Data Yet'];
            $data = [1];
            $colors = ['#e2e8f0'];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Usage',
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                    'borderWidth' => 0,
                    'hoverOffset' => 10,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}

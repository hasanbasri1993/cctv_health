<?php

namespace App\Filament\Resources\CctvResource\Widgets;

use App\Models\Cctv;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class TemperatureChart extends ChartWidget
{
    protected static ?string $heading = 'Temperature History';

    protected static ?string $maxHeight = '300px';

    protected static ?string $pollingInterval = '30s';

    public ?string $filter = 'month';

    public ?Cctv $record = null;

    protected function getData(): array
    {
        // Get record from widget data
        $this->record = $this->record ?? $this->getRecord();

        if (! $this->record) {
            // If still no record, try to get it from the page's widget data
            $page = \Filament\Facades\Filament::getCurrentPanel()?->getPage();
            if (method_exists($page, 'getWidgetData')) {
                $this->record = $page->getWidgetData()['record'] ?? null;
            }
        }

        if (! $this->record) {
            return [
                'datasets' => [
                    [
                        'label' => 'Temperature (°C)',
                        'data' => [],
                        'borderColor' => 'rgb(75, 192, 192)',
                        'backgroundColor' => 'rgba(75, 192, 192, 0.1)',
                        'fill' => true,
                        'tension' => 0.4,
                        'borderWidth' => 2,
                        'pointRadius' => 3,
                    ],
                ],
                'labels' => [],
            ];
        }

        // Set date range based on filter
        $start = match ($this->filter) {
            'today' => now()->startOfDay(),
            'month' => now()->startOfMonth(),
            default => now()->startOfWeek(),
        };

        $end = match ($this->filter) {
            'today' => now()->endOfDay(),
            'month' => now()->endOfMonth(),
            default => now()->endOfWeek(),
        };

        // Set interval based on time range
        $interval = $this->filter === 'today' ? 'perHour' : 'perDay';

        $data = Trend::model(\App\Models\Health::class)
            ->dateColumn('created_at')
            ->between($start, $end)
            ->{$interval}()
            ->average('temprature');

        // Format labels based on interval
        $labels = $data->map(function (TrendValue $value) use ($interval) {
            $date = Carbon::parse($value->date);

            return $interval === 'perHour'
                ? $date->format('H:i')
                : $date->format('M d');
        });

        return [
            'datasets' => [
                [
                    'label' => 'Temperature (°C)',
                    'data' => $data->map(fn (TrendValue $value) => round($value->aggregate, 1)),
                    'borderColor' => 'rgb(75, 192, 192)',
                    'backgroundColor' => 'rgba(75, 192, 192, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                    'borderWidth' => 2,
                    'pointRadius' => 3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Today',
            'week' => 'This Week',
            'month' => 'This Month',
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => false,
                    'title' => [
                        'display' => true,
                        'text' => 'Temperature (°C)',
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => $this->getFilterLabel(),
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }

    protected function getFilterLabel(): string
    {
        return match ($this->filter) {
            'today' => 'Time',
            'month' => 'Day of Month',
            default => 'Day of Week',
        };
    }
}

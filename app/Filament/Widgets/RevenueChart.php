<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class RevenueChart extends ChartWidget
{
    protected  ?string $heading = 'Pendapatan Harian';
    protected static ?int $sort = 2;
    protected  ?string $maxHeight = '300px';

    public ?string $filter = '7days';

    protected function getData(): array
    {
        $days = match ($this->filter) {
            '7days' => 7,
            '14days' => 14,
            '30days' => 30,
            '90days' => 90,
            default => 7,
        };

        $data = Trend::model(Transaction::class)
            ->between(
                start: now()->subDays($days),
                end: now(),
            )
            ->perDay()
            ->sum('total_after_discount');

        return [
            'datasets' => [
                [
                    'label' => 'Pendapatan',
                    'data' => $data->map(fn(TrendValue $value) => $value->aggregate),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $data->map(fn(TrendValue $value) => date('d M', strtotime($value->date))),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            '7days' => '7 Hari Terakhir',
            '14days' => '14 Hari Terakhir',
            '30days' => '30 Hari Terakhir',
            '90days' => '90 Hari Terakhir',
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'ticks' => [
                        'callback' => 'function(value) { return "Rp " + value.toLocaleString("id-ID"); }',
                    ],
                ],
            ],
        ];
    }
}

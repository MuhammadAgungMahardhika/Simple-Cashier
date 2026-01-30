<?php

namespace App\Filament\Widgets;

use App\Models\Enums\TransactionStatusEnum;
use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class MonthlyRevenueChart extends ChartWidget
{
    protected  ?string $heading = 'Perbandingan Pendapatan Bulanan';
    protected static ?int $sort = 5;
    protected  ?string $maxHeight = '300px';
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $currentYear = now()->year;
        $lastYear = now()->subYear()->year;

        $currentYearData = [];
        $lastYearData = [];

        for ($month = 1; $month <= 12; $month++) {
            // Current year
            $currentRevenue = Transaction::whereYear('transaction_date', $currentYear)
                ->whereMonth('transaction_date', $month)
                ->where('status', TransactionStatusEnum::Paid->value)
                ->sum('total_after_discount');
            $currentYearData[] = $currentRevenue;

            // Last year
            $lastRevenue = Transaction::whereYear('transaction_date', $lastYear)
                ->whereMonth('transaction_date', $month)
                ->where('status', TransactionStatusEnum::Paid->value)
                ->sum('total_after_discount');
            $lastYearData[] = $lastRevenue;
        }

        return [
            'datasets' => [
                [
                    'label' => $currentYear,
                    'data' => $currentYearData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => $lastYear,
                    'data' => $lastYearData,
                    'backgroundColor' => 'rgba(156, 163, 175, 0.2)',
                    'borderColor' => 'rgb(156, 163, 175)',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
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

<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PaymentMethodChart extends ChartWidget
{
    protected  ?string $heading = 'Metode Pembayaran';
    protected static ?int $sort = 4;
    protected  ?string $maxHeight = '300px';

    public ?string $filter = 'month';

    protected function getData(): array
    {
        $dateFilter = match ($this->filter) {
            'today' => now()->startOfDay(),
            'week' => now()->subDays(7),
            'month' => now()->subDays(30),
            'year' => now()->subYear(),
            default => now()->subDays(30),
        };

        $data = Transaction::select('payment_method', DB::raw('COUNT(*) as total'))
            ->where('transaction_date', '>=', $dateFilter)
            ->groupBy('payment_method')
            ->get();

        $labels = $data->pluck('payment_method')->map(fn($method) => match ($method) {
            'cash' => 'Cash',
            'qris' => 'QRIS',
            'transfer' => 'Transfer',
            default => ucfirst($method),
        })->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Transaksi',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => [
                        'rgba(34, 197, 94, 0.8)',  // green for cash
                        'rgba(59, 130, 246, 0.8)',  // blue for qris
                        'rgba(168, 85, 247, 0.8)',  // purple for transfer
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Hari Ini',
            'week' => '7 Hari Terakhir',
            'month' => '30 Hari Terakhir',
            'year' => '1 Tahun Terakhir',
        ];
    }
}

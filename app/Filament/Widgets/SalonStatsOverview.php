<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use App\Models\Customer;
use App\Models\Enums\TransactionStatusEnum;
use App\Models\Service;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class SalonStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();
        $startOfMonth = now()->startOfMonth();
        $startOfLastMonth = now()->subMonth()->startOfMonth();
        $endOfLastMonth = now()->subMonth()->endOfMonth();

        // 1. Pendapatan Hari Ini
        $todayRevenue = Transaction::whereDate('transaction_date', $today)
            ->where('status',  TransactionStatusEnum::Paid->value)
            ->sum('total_after_discount');

        $yesterdayRevenue = Transaction::whereDate('transaction_date', $yesterday)
            ->where('status',  TransactionStatusEnum::Paid->value)
            ->sum('total_after_discount');

        $revenueChange = $yesterdayRevenue > 0
            ? round((($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100, 1)
            : 0;

        // 2. Transaksi Hari Ini
        $todayTransactions = Transaction::whereDate('transaction_date', $today)
            ->count();

        $yesterdayTransactions = Transaction::whereDate('transaction_date', $yesterday)
            ->count();

        $transactionChange = $yesterdayTransactions > 0
            ? round((($todayTransactions - $yesterdayTransactions) / $yesterdayTransactions) * 100, 1)
            : 0;

        // 3. Pendapatan Bulan Ini
        $monthRevenue = Transaction::whereDate('transaction_date', '>=', $startOfMonth)
            ->where('status',  TransactionStatusEnum::Paid->value)
            ->sum('total_after_discount');

        $lastMonthRevenue = Transaction::whereBetween('transaction_date', [$startOfLastMonth, $endOfLastMonth])
            ->where('status',  TransactionStatusEnum::Paid->value)
            ->sum('total_after_discount');

        $monthRevenueChange = $lastMonthRevenue > 0
            ? round((($monthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;

        // 4. Total Pelanggan Aktif (yang pernah transaksi)
        $activeCustomers = Customer::whereHas('transactions')->count();

        // 5. Pelanggan Baru Bulan Ini
        $newCustomersThisMonth = Customer::whereDate('created_at', '>=', $startOfMonth)->count();

        // 6. Transaksi Pending
        $pendingTransactions = Transaction::where('status', TransactionStatusEnum::Pending->value)->count();

        return [
            Stat::make('Pendapatan Hari Ini', 'Rp ' . number_format($todayRevenue, 0, ',', '.'))
                ->description($revenueChange >= 0 ? "+{$revenueChange}% dari kemarin" : "{$revenueChange}% dari kemarin")
                ->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueChange >= 0 ? 'success' : 'danger')
                ->chart($this->getRevenueChartData(7)),

            Stat::make('Transaksi Hari Ini', $todayTransactions)
                ->description($transactionChange >= 0 ? "+{$transactionChange}% dari kemarin" : "{$transactionChange}% dari kemarin")
                ->descriptionIcon($transactionChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($transactionChange >= 0 ? 'success' : 'danger')
                ->chart($this->getTransactionChartData(7)),

            Stat::make('Pendapatan Bulan Ini', 'Rp ' . number_format($monthRevenue, 0, ',', '.'))
                ->description($monthRevenueChange >= 0 ? "+{$monthRevenueChange}% dari bulan lalu" : "{$monthRevenueChange}% dari bulan lalu")
                ->descriptionIcon($monthRevenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($monthRevenueChange >= 0 ? 'success' : 'danger'),

            Stat::make('Total Pelanggan', $activeCustomers)
                ->description("{$newCustomersThisMonth} pelanggan baru bulan ini")
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('info'),

            Stat::make('Transaksi Pending', $pendingTransactions)
                ->description('Perlu diselesaikan')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url(route('filament.app.resources.transactions.index', ['tableFilters' => ['status' => TransactionStatusEnum::Pending->value]])),

            Stat::make('Layanan Terpopuler', $this->getMostPopularService())
                ->description('Layanan paling banyak dipilih')
                ->descriptionIcon('heroicon-m-star')
                ->color('success'),
        ];
    }

    private function getRevenueChartData(int $days): array
    {
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $revenue = Transaction::whereDate('transaction_date', $date)
                ->where('status',  TransactionStatusEnum::Paid->value)
                ->sum('total_after_discount');
            $data[] = $revenue;
        }
        return $data;
    }

    private function getTransactionChartData(int $days): array
    {
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $count = Transaction::whereDate('transaction_date', $date)->count();
            $data[] = $count;
        }
        return $data;
    }

    private function getMostPopularService(): string
    {
        $service = DB::table('transaction_details')
            ->select('service_name', DB::raw('COUNT(*) as total'))
            ->groupBy('service_name')
            ->orderBy('total', 'desc')
            ->first();

        return $service ? $service->service_name : 'Belum ada data';
    }
}

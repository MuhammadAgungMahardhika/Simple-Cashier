<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Enums\TransactionStatusEnum;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopCustomers extends BaseWidget
{
    protected static ?int $sort = 7;
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Pelanggan Setia';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Customer::query()
                    ->select('customers.*')
                    ->selectRaw('COUNT(transactions.id) as total_transactions')
                    ->selectRaw('COALESCE(SUM(transactions.total_after_discount), 0) as total_spent')
                    ->leftJoin('transactions', function ($join) {
                        $join->on('customers.id', '=', 'transactions.customer_id')
                            ->where('transactions.status', '=',  TransactionStatusEnum::Paid->value);
                    })
                    ->groupBy('customers.id', 'customers.name', 'customers.phone', 'customers.email', 'customers.address', 'customers.created_at', 'customers.updated_at', 'customers.created_by', 'customers.updated_by')
                    ->orderByDesc('total_spent')
                    ->having('total_transactions', '>', 0)
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Pelanggan')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('No. Telepon')
                    ->searchable(),

                Tables\Columns\TextColumn::make('total_transactions')
                    ->label('Total Kunjungan')
                    ->badge()
                    ->color('info')
                    ->alignCenter()
                    ->state(fn($record) => $record->total_transactions ?? 0),

                Tables\Columns\TextColumn::make('total_spent')
                    ->label('Total Belanja')
                    ->money('IDR')
                    ->weight('bold')
                    ->color('success')
                    ->state(fn($record) => $record->total_spent ?? 0),
            ]);
    }
}

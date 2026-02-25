<?php

namespace App\Filament\Resources\TransactionReports;

use App\Filament\Resources\TransactionReports\Pages\CreateTransactionReport;
use App\Filament\Resources\TransactionReports\Pages\EditTransactionReport;
use App\Filament\Resources\TransactionReports\Pages\ListTransactionReports;
use App\Filament\Resources\TransactionReports\Pages\ViewTransactionReport;
use App\Filament\Resources\TransactionReports\Schemas\TransactionReportForm;
use App\Filament\Resources\TransactionReports\Schemas\TransactionReportInfolist;
use App\Filament\Resources\TransactionReports\Tables\TransactionReportsTable;
use App\Models\Enums\TransactionStatusEnum;
use App\Models\Transaction;
use App\Models\TransactionReport;
use App\Policies\TransactionPolicy;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class TransactionReportResource extends Resource
{
    protected static ?string $model = Transaction::class;
    protected static ?string $navigationLabel = 'Laporan Transaksi';
    protected static ?string $label = 'Laporan Transaksi';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentCheck;
    protected static string|UnitEnum|null $navigationGroup = 'Laporan';
    protected static ?string $recordTitleAttribute = 'transaction';

    public static function form(Schema $schema): Schema
    {
        return TransactionReportForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('transaction_code')
            ->defaultSort('transaction_date', 'desc')
            ->columns([
                TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->date('d-m-Y')
                    ->sortable(),

                TextColumn::make('transaction_code')
                    ->label('Kode Transaksi')
                    ->searchable()
                    ->weight(FontWeight::Bold)
                    ->color('primary'),

                TextColumn::make('customer.name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable()
                    ->description(function ($record) {
                        $exp = $record->customer?->member_expired_at;
                        if (!$exp) return null;

                        return \Carbon\Carbon::parse($exp)->gte(now()->startOfDay())
                            ? '🟢 Member s/d ' . \Carbon\Carbon::parse($exp)->format('d/m/Y')
                            : '🔴 Member expired';
                    })
                    ->limit(30),

                TextColumn::make('transactionDetails.item_name')
                    ->label('Item')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList(),

                TextColumn::make('transactionDetails.item_type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'member'  => 'info',
                        'package' => 'warning',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'member'  => 'Member',
                        'package' => 'Paket',
                        default   => 'Normal',
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn($state) => TransactionStatusEnum::labels()[$state])
                    ->color(fn(string $state): string => TransactionStatusEnum::color($state)),

                TextColumn::make('payment_method')
                    ->label('Pembayaran')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'cash'     => 'Cash',
                        'qris'     => 'QRIS',
                        'transfer' => 'Transfer',
                        default    => $state,
                    }),

                TextColumn::make('total_after_discount')
                    ->label('Total')
                    ->numeric()
                    ->money('IDR')
                    ->alignEnd()
                    ->sortable()
                    ->size(TextSize::Medium)
                    ->weight(FontWeight::Bold)
                    ->color('success'),
            ])

            ->filters([
                Filter::make('transaction_date')
                    ->schema([
                        DatePicker::make('from')
                            ->default(now()->startOfDay())
                            ->label('Dari Tanggal'),
                        DatePicker::make('to')
                            ->default(now()->endOfDay())
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn($q, $date) => $q->whereDate('transaction_date', '>=', $date))
                            ->when($data['to'],   fn($q, $date) => $q->whereDate('transaction_date', '<=', $date));
                    })
                    ->columnSpan(2)->columns(2),

                SelectFilter::make('customer_id')
                    ->label('Pelanggan')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->options([
                        'cash'     => 'Cash',
                        'qris'     => 'QRIS',
                        'transfer' => 'Transfer',
                    ]),

                SelectFilter::make('discount_id')
                    ->label('Diskon')
                    ->relationship('discount', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(TransactionStatusEnum::labels()),
            ], layout: FiltersLayout::AboveContent)

            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function canViewAny(): bool
    {
        return app(TransactionPolicy::class)->viewAny(Auth::user());
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransactionReports::route('/'),

        ];
    }
}

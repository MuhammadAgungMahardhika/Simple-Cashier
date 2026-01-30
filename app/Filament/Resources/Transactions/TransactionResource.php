<?php

namespace App\Filament\Resources\Transactions;

use App\Filament\Resources\Transactions\Pages\ManageTransactions;
use App\Models\Transaction;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('transaction_code')
                    ->default(fn() => 'TRX-' . date('Ymd') . '-' . strtoupper(uniqid()))
                    ->dehydrated(),

                Hidden::make('transaction_date')
                    ->default(now())
                    ->dehydrated(),

                Hidden::make('status')
                    ->default('pending')
                    ->dehydrated(),

                Select::make('customer_id')
                    ->label('Nama Pelanggan')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull(),

                Repeater::make('transactionDetails')
                    ->relationship()
                    ->table([
                        TableColumn::make('Layanan'),
                        TableColumn::make('Kuantitas'),
                        TableColumn::make('Harga'),
                        TableColumn::make('Subtotal'),
                    ])
                    ->label('Detail Transaksi')
                    ->schema([
                        Select::make('service_id')
                            ->label('Layanan')
                            ->relationship('service', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                $service = \App\Models\Service::find($state);

                                if ($service) {
                                    // Set service_name untuk disimpan ke database
                                    $set('service_name', $service->name);
                                    $set('price', $service->price);

                                    $qty = $get('quantity') ?? 1;
                                    $subtotal = $service->price * $qty;
                                    $set('subtotal', $subtotal);

                                    // Recalculate total menggunakan path ABSOLUT
                                    $details = $get('../../transactionDetails') ?? [];
                                    $totalSubtotal = collect($details)->sum(fn($item) => floatval($item['subtotal'] ?? 0));

                                    $set('../../total_before_discount', $totalSubtotal);
                                    $set('../../subtotal', $totalSubtotal);

                                    // Calculate discount
                                    $discountId = $get('../../discount_id');
                                    $discountAmount = 0;

                                    if ($discountId) {
                                        $discount = \App\Models\Discount::find($discountId);
                                        if ($discount) {
                                            if ($discount->type === 'percentage') {
                                                $discountAmount = $totalSubtotal * ($discount->value / 100);
                                            } else {
                                                $discountAmount = $discount->value;
                                            }
                                        }
                                    }

                                    $set('../../discount_amount', $discountAmount);
                                    $set('../../total_after_discount', $totalSubtotal - $discountAmount);
                                }
                            })
                            ->columnSpan(3),

                        Hidden::make('service_name')
                            ->dehydrated(),

                        TextInput::make('quantity')
                            ->label('Qty')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                $price = $get('price') ?? 0;
                                $subtotal = $price * $state;
                                $set('subtotal', $subtotal);

                                // Recalculate total menggunakan path ABSOLUT
                                $details = $get('../../transactionDetails') ?? [];
                                $totalSubtotal = collect($details)->sum(fn($item) => floatval($item['subtotal'] ?? 0));

                                $set('../../total_before_discount', $totalSubtotal);
                                $set('../../subtotal', $totalSubtotal);

                                // Calculate discount
                                $discountId = $get('../../discount_id');
                                $discountAmount = 0;

                                if ($discountId) {
                                    $discount = \App\Models\Discount::find($discountId);
                                    if ($discount) {
                                        if ($discount->type === 'percentage') {
                                            $discountAmount = $totalSubtotal * ($discount->value / 100);
                                        } else {
                                            $discountAmount = $discount->value;
                                        }
                                    }
                                }

                                $set('../../discount_amount', $discountAmount);
                                $set('../../total_after_discount', $totalSubtotal - $discountAmount);
                            })
                            ->columnSpan(1),

                        TextInput::make('price')
                            ->label('Harga')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->columnSpan(1),

                        TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->columnSpan(1),
                    ])
                    ->columns(6)
                    ->defaultItems(1)
                    ->addActionLabel('Tambah Layanan')
                    ->deleteAction(
                        fn($action) => $action->after(function (Get $get, Set $set) {
                            // Recalculate setelah delete
                            $details = $get('transactionDetails') ?? [];
                            $totalSubtotal = collect($details)->sum(fn($item) => floatval($item['subtotal'] ?? 0));

                            $set('total_before_discount', $totalSubtotal);
                            $set('subtotal', $totalSubtotal);

                            // Calculate discount
                            $discountId = $get('discount_id');
                            $discountAmount = 0;

                            if ($discountId) {
                                $discount = \App\Models\Discount::find($discountId);
                                if ($discount) {
                                    if ($discount->type === 'percentage') {
                                        $discountAmount = $totalSubtotal * ($discount->value / 100);
                                    } else {
                                        $discountAmount = $discount->value;
                                    }
                                }
                            }

                            $set('discount_amount', $discountAmount);
                            $set('total_after_discount', $totalSubtotal - $discountAmount);
                        })
                    )
                    ->live()
                    ->columnSpanFull()
                    ->required(),



                Section::make('Ringkasan Pembayaran')
                    ->columns(1)
                    ->inlineLabel()
                    ->schema([
                        TextInput::make('total_before_discount')
                            ->label('Subtotal')
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->numeric()
                            ->default(0),

                        TextInput::make('discount_amount')
                            ->label('Diskon')
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->numeric()
                            ->default(0),

                        TextInput::make('total_after_discount')
                            ->label('TOTAL')
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->numeric()
                            ->default(0)
                            ->extraAttributes([
                                'class' => 'text-xl font-bold'
                            ]),

                        Hidden::make('subtotal')
                            ->dehydrated()
                            ->default(0),
                    ])
                    ->columnSpanFull(),

                Radio::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->options([
                        'cash' => 'Cash',
                        'qris' => 'QRIS',
                        'transfer' => 'Transfer',
                    ])
                    ->default('cash')
                    ->inline()
                    ->required(),

                Select::make('discount_id')
                    ->label('Diskon')
                    ->relationship('discount', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        $details = $get('transactionDetails') ?? [];
                        $totalSubtotal = collect($details)->sum(fn($item) => floatval($item['subtotal'] ?? 0));

                        $set('total_before_discount', $totalSubtotal);
                        $set('subtotal', $totalSubtotal);

                        // Calculate discount
                        $discountId = $get('discount_id');
                        $discountAmount = 0;

                        if ($discountId) {
                            $discount = \App\Models\Discount::find($discountId);
                            if ($discount) {
                                if ($discount->type === 'percentage') {
                                    $discountAmount = $totalSubtotal * ($discount->value / 100);
                                } else {
                                    $discountAmount = $discount->value;
                                }
                            }
                        }

                        $set('discount_amount', $discountAmount);
                        $set('total_after_discount', $totalSubtotal - $discountAmount);
                    }),
            ]);
    }
    protected static function recalculateTotal(Get $get, Set $set): void
    {
        // Get all transaction details
        $details = collect($get('transactionDetails') ?? []);

        // Calculate subtotal from all items
        $subtotal = $details->sum(fn($item) => floatval($item['subtotal'] ?? 0));

        // Set total_before_discount and subtotal (keduanya sama)
        $set('total_before_discount', $subtotal);
        $set('subtotal', $subtotal);

        // Calculate discount
        $discountId = $get('discount_id');
        $discountAmount = 0;

        if ($discountId) {
            $discount = \App\Models\Discount::find($discountId);
            if ($discount) {
                if ($discount->type === 'percentage') {
                    $discountAmount = $subtotal * ($discount->value / 100);
                } else {
                    $discountAmount = $discount->value;
                }
            }
        }

        // Set discount amount and total after discount
        $set('discount_amount', $discountAmount);
        $set('total_after_discount', $subtotal - $discountAmount);
    }


    public static function table(Table $table): Table
    {
        return $table
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
                    ->limit(25),

                TextColumn::make('transactionDetails.service.name')
                    ->label('Layanan')
                    ->badge()
                    ->listWithLineBreaks()
                    ->separator(',')
                    ->limitList(2)
                    ->expandableLimitedList(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'unpaid' => 'danger',
                        'cancelled' => 'gray',
                    }),

                TextColumn::make('payment_method')
                    ->label('Pembayaran')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'cash' => 'Cash',
                        'qris' => 'QRIS',
                        'transfer' => 'Transfer',
                        default => $state,
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
                            ->label('Dari Tanggal'),
                        DatePicker::make('to')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn($query, $date) => $query->whereDate('transaction_date', '>=', $date))
                            ->when($data['to'], fn($query, $date) => $query->whereDate('transaction_date', '<=', $date));
                    })->columnSpan(2)->columns(2),
                SelectFilter::make('customer_id')
                    ->label('Pelanggan')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->options([
                        'cash' => 'Cash',
                        'qris' => 'QRIS',
                        'transfer' => 'Transfer',
                    ]),
                SelectFilter::make('discount_id')
                    ->label('Diskon')
                    ->relationship('discount', 'name')
                    ->searchable()
                    ->preload(),



            ], layout: FiltersLayout::AboveContent)
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }


    public static function getPages(): array
    {
        return [
            'index' => ManageTransactions::route('/'),
        ];
    }
}

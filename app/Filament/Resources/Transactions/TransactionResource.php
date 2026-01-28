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
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('transaction_date')
                    ->required()
                    ->default(now()),

                Select::make('customer_id')
                    ->label('Nama Pelanggan')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Hidden::make('status')
                    ->required()
                    ->default('pending'),

                Repeater::make('transactionDetails')
                    ->table([
                        TableColumn::make('layanan'),
                        TableColumn::make('kuantitas'),
                        TableColumn::make('harga'),
                        TableColumn::make('subtotal'),
                    ])
                    ->label('Detail Transaksi')
                    ->relationship()
                    ->schema([
                        Select::make('service_id')
                            ->label('Layanan')
                            ->relationship('service', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set, Get $get, $livewire) {
                                $service = \App\Models\Service::find($state);

                                if ($service) {
                                    $set('price', $service->price);
                                    $qty = $get('quantity') ?? 1;
                                    $set('subtotal', $service->price * $qty);
                                }

                                // Recalculate menggunakan livewire instance
                                $details = $get('../../transactionDetails') ?? [];
                                $subtotal = collect($details)->sum(fn($item) => $item['subtotal'] ?? 0);

                                $set('../../total_before_discount', $subtotal);

                                $discountId = $get('../../discount_id');
                                $discountAmount = 0;

                                if ($discountId) {
                                    $discount = \App\Models\Discount::find($discountId);
                                    if ($discount) {
                                        $discountAmount = $discount->type === 'percentage'
                                            ? ($subtotal * $discount->value / 100)
                                            : $discount->value;
                                    }
                                }

                                $set('../../discount_amount', $discountAmount);
                                $set('../../total_after_discount', $subtotal - $discountAmount);
                            })
                            ->columnSpan(2),

                        TextInput::make('quantity')
                            ->label('Qty')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                $price = $get('price') ?? 0;
                                $set('subtotal', $price * $state);

                                // Recalculate menggunakan path relatif
                                $details = $get('../../transactionDetails') ?? [];
                                $subtotal = collect($details)->sum(fn($item) => $item['subtotal'] ?? 0);

                                $set('../../total_before_discount', $subtotal);

                                $discountId = $get('../../discount_id');
                                $discountAmount = 0;

                                if ($discountId) {
                                    $discount = \App\Models\Discount::find($discountId);
                                    if ($discount) {
                                        $discountAmount = $discount->type === 'percentage'
                                            ? ($subtotal * $discount->value / 100)
                                            : $discount->value;
                                    }
                                }

                                $set('../../discount_amount', $discountAmount);
                                $set('../../total_after_discount', $subtotal - $discountAmount);
                            }),

                        TextInput::make('price')
                            ->label('Harga')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->required(),

                        TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(6)
                    ->defaultItems(1)
                    ->addActionLabel('Tambah Layanan')
                    ->live()
                    ->columnSpanFull()
                    ->required(),
                Select::make('discount_id')
                    ->label('Diskon')
                    ->relationship('discount', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->reactive()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        self::recalculateTotal($get, $set);
                    }),

                Section::make('Ringkasan Pembayaran')
                    ->columns(1)
                    ->inlineLabel()
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('total_before_discount')
                            ->label('Subtotal')
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('discount_amount')
                            ->label('Diskon')
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('total_after_discount')
                            ->label('TOTAL')
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->extraAttributes([
                                'class' => 'text-xl font-bold'
                            ]),
                    ]),

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

            ]);
    }

    protected static function recalculateTotal(Get $get, Set $set): void
    {
        $subtotal = (float) $get('total_before_discount');
        $discount = null;

        if ($get('discount_id')) {
            $discount = \App\Models\Discount::find($get('discount_id'));
        }

        $discountAmount = 0;

        if ($discount) {
            $discountAmount = $discount->type === 'percentage'
                ? $subtotal * ($discount->value / 100)
                : $discount->value;
        }

        $set('discount_amount', $discountAmount);
        $set('total_after_discount', max($subtotal - $discountAmount, 0));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_code')
                    ->searchable(),
                TextColumn::make('discount_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('customer_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('service_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_before_discount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('discount_amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_after_discount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('transaction_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->badge(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('created_by')
                    ->searchable(),
                TextColumn::make('updated_by')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
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

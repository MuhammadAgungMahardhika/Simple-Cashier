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
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
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
                Select::make('customer_id')
                    ->label('Nama pelanggan')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('total_before_discount')
                    ->label('Total Sebelum Diskon')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(),

                TextInput::make('discount_amount')
                    ->label('Jumlah Diskon')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(),

                TextInput::make('total_after_discount')
                    ->label('Total')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(),

                DatePicker::make('transaction_date')
                    ->label('Tanggal Transaksi')
                    ->required()
                    ->default(now()),

                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'unpaid' => 'Unpaid',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('pending')
                    ->required(),

                Repeater::make('transactionDetails')
                    ->label('Detail Transaksi')
                    ->relationship()
                    ->table([
                        TableColumn::make('Nama layanan'),
                        TableColumn::make('Kuantitas'),
                        TableColumn::make('Harga'),
                        TableColumn::make('Subtotal'),
                    ])
                    ->schema([
                        Select::make('service_id')
                            ->label('Service')
                            ->relationship('service', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('quantity')
                            ->numeric()
                            ->minValue(1)

                            ->default(1)
                            ->required(),
                        TextInput::make('price')
                            ->numeric()
                            ->prefix('Rp')
                            ->readOnly()
                            ->required(),
                    ])
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        $subtotal = 0;
                        $details = $get('transactionDetails') ?? [];

                        foreach ($details as $detail) {
                            $subtotal += ($detail['quantity'] ?? 0) * ($detail['price'] ?? 0);
                        }

                        $set('total_before_discount', $subtotal);
                        self::recalculateTotal($get, $set);
                    })->columnSpanFull(),

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
                    ->reactive()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        self::recalculateTotal($get, $set);
                    }),

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

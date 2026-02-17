<?php

namespace App\Filament\Resources\Transactions;

use App\Filament\Resources\TransactionResource\Actions\PrintReceiptAction;
use App\Filament\Resources\Transactions\Actions\TransactionActions;
use App\Filament\Resources\Transactions\Pages\ManageTransactions;
use App\Models\Discount;
use App\Models\Enums\TransactionStatusEnum;
use App\Models\Package;
use App\Models\Service;
use App\Models\Transaction;
use BackedEnum;
use Filament\Actions\ActionGroup;
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
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
    protected static ?string $navigationLabel = 'Transaksi';
    protected static ?string $label = 'Transaksi';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentCheck;

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Tentukan apakah customer saat ini adalah member.
     * Sesuaikan logika ini dengan field/relasi di model Customer Anda.
     */
    protected static function isCustomerMember(?int $customerId): bool
    {
        if (!$customerId) {
            return false;
        }

        // Sesuaikan dengan kondisi "member" di model Customer Anda.
        // Contoh: customer punya kolom `is_member` atau relasi membership aktif.
        $customer = \App\Models\Customer::find($customerId);
        return $customer?->is_member ?? false;
    }

    /**
     * Hitung harga yang tepat berdasarkan item_type dan status member customer.
     */
    protected static function resolvePrice(Service $service, string $itemType, bool $isMember): array
    {
        return match ($itemType) {
            'member'  => [
                'price'      => (float) $service->member_price,
                'fee_amount' => (float) ($service->member_fee ?? 0),
            ],
            'package' => [
                'price'      => $isMember
                    ? (float) $service->member_package_price
                    : (float) $service->package_price,
                'fee_amount' => (float) ($isMember ? ($service->member_fee ?? 0) : ($service->fee ?? 0)),
            ],
            default   => [ // 'normal'
                'price'      => (float) $service->price,
                'fee_amount' => (float) ($service->fee ?? 0),
            ],
        };
    }

    /**
     * Recalculate totals (total_before_discount, discount_amount, total_after_discount).
     * Dapat dipanggil dari mana saja dalam form.
     */
    protected static function recalculateTotals(Get $get, Set $set): void
    {
        $details       = collect($get('transactionDetails') ?? []);
        $subtotal      = $details->sum(fn($item) => (float) ($item['subtotal'] ?? 0));

        $set('subtotal',              $subtotal);
        $set('total_before_discount', $subtotal);

        $discountId     = $get('discount_id');
        $discountAmount = 0;

        if ($discountId) {
            $discount = Discount::find($discountId);
            if ($discount) {
                $discountAmount = $discount->type === 'percentage'
                    ? $subtotal * ($discount->value / 100)
                    : (float) $discount->value;
            }
        }

        $set('discount_amount',       $discountAmount);
        $set('total_after_discount',  $subtotal - $discountAmount);
    }

    // =========================================================================
    // FORM
    // =========================================================================

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            // ── Hidden system fields ─────────────────────────────────────────
            Hidden::make('transaction_code')
                ->default(fn() => 'TRX-' . date('Ymd') . '-' . strtoupper(uniqid()))
                ->dehydrated(),

            Hidden::make('transaction_date')
                ->default(now())
                ->dehydrated(),

            Hidden::make('status')
                ->default(TransactionStatusEnum::Pending->value)
                ->dehydrated(),

            // ── Customer ─────────────────────────────────────────────────────
            Select::make('customer_id')
                ->label('Nama Pelanggan')
                ->relationship('customer', 'name')
                ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->name} ({$record->phone})")
                ->searchable()
                ->preload()
                ->required()
                ->live()          // live agar perubahan customer bisa mempengaruhi harga
                ->columnSpanFull()
                ->afterStateUpdated(function (Get $get, Set $set) {
                    // Ketika customer berganti, reset semua detail agar harga dihitung ulang
                    // sesuai status member customer yang baru.
                    // Jika ingin mempertahankan item dan hanya update harga, loop details di sini.
                    static::recalculateTotals($get, $set);
                })
                ->createOptionForm([
                    Grid::make()->columns(2)->schema([
                        TextInput::make('code')->label('Kode Pelanggan')->readOnly()->visibleOn(['edit'])->required(),
                        TextInput::make('name')->label('Nama Pelanggan')->required(),
                        TextInput::make('phone')->label('Nomor Telepon/Wa')->tel()->required(),
                        TextInput::make('email')->label('Email')->email(),
                        Textarea::make('address')->label('Alamat'),
                    ]),
                ])
                ->createOptionModalHeading('Tambah Pelanggan Baru')
                ->editOptionForm([
                    Grid::make()->columns(2)->schema([
                        TextInput::make('code')->label('Kode Pelanggan')->readOnly()->visibleOn(['edit'])->required(),
                        TextInput::make('name')->label('Nama Pelanggan')->required(),
                        TextInput::make('phone')->label('Nomor Telepon/Wa')->tel()->required(),
                        TextInput::make('email')->label('Email')->email(),
                        Textarea::make('address')->label('Alamat'),
                    ]),
                ])
                ->editOptionModalHeading('Ubah Data Pelanggan'),

            // ── Transaction Details (Repeater) ────────────────────────────────
            Repeater::make('transactionDetails')
                ->relationship()
                ->label('Detail Transaksi')
                ->table([
                    TableColumn::make('Tipe'),
                    TableColumn::make('Layanan / Paket'),
                    TableColumn::make('Terapis'),
                    TableColumn::make('Qty'),
                    TableColumn::make('Harga'),
                    TableColumn::make('Subtotal'),
                ])
                ->schema([

                    // -- Tipe item --------------------------------------------------
                    Select::make('item_type')
                        ->label('Tipe')
                        ->options([
                            'normal'  => 'Normal',
                            'member'  => 'Member',
                            'package' => 'Paket',
                        ])
                        ->default('normal')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                            // Reset service/package selection when type changes
                            $set('service_id', null);
                            $set('package_id', null);
                            $set('item_name',  null);
                            $set('price',      0);
                            $set('fee_amount', 0);
                            $set('subtotal',   0);
                            static::recalculateTotals($get, $set);
                        })
                        ->columnSpan(1),

                    // -- Service (untuk tipe normal & member) -----------------------
                    Select::make('service_id')
                        ->label('Layanan')
                        ->relationship('service', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->live(onBlur: true)
                        ->visible(fn(Get $get) => in_array($get('item_type'), ['normal', 'member']))
                        ->required(fn(Get $get) => in_array($get('item_type'), ['normal', 'member']))
                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                            if (!$state) return;

                            $service  = Service::find($state);
                            if (!$service) return;

                            $customerId = $get('../../customer_id');
                            $isMember   = static::isCustomerMember($customerId);
                            $itemType   = $get('item_type') ?? 'normal';

                            ['price' => $price, 'fee_amount' => $fee] =
                                static::resolvePrice($service, $itemType, $isMember);

                            $qty = max(1, (int) ($get('quantity') ?? 1));

                            $set('item_name',  $service->name);
                            $set('price',      $price);
                            $set('fee_amount', $fee);
                            $set('subtotal',   $price * $qty);

                            static::recalculateTotals($get, $set);
                        })
                        ->columnSpan(2),

                    // -- Package (untuk tipe package) -------------------------------
                    Select::make('package_id')
                        ->label('Paket')
                        ->relationship('package', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->live(onBlur: true)
                        ->visible(fn(Get $get) => $get('item_type') === 'package')
                        ->required(fn(Get $get) => $get('item_type') === 'package')
                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                            if (!$state) return;

                            $package = Package::find($state);
                            if (!$package) return;

                            // Untuk paket, kita tidak otomatis set price per-item dari service
                            // karena satu paket bisa berisi banyak service.
                            // Harga paket diambil dari service pertama dalam paket,
                            // atau Anda bisa menambahkan kolom `price`/`member_price` di tabel packages.
                            // Di sini kita set item_name dan biarkan user isi harga manual,
                            // atau sesuaikan dengan bisnis logic Anda.
                            $customerId = $get('../../customer_id');
                            $isMember   = static::isCustomerMember($customerId);

                            // Ambil service pertama dalam paket untuk referensi harga
                            $firstDetail = $package->packageDetails()->with('service')->first();
                            $price       = 0;
                            $fee         = 0;

                            if ($firstDetail?->service) {
                                ['price' => $price, 'fee_amount' => $fee] =
                                    static::resolvePrice($firstDetail->service, 'package', $isMember);
                            }

                            $qty = max(1, (int) ($get('quantity') ?? 1));

                            $set('item_name',  $package->name);
                            $set('price',      $price);
                            $set('fee_amount', $fee);
                            $set('subtotal',   $price * $qty);

                            static::recalculateTotals($get, $set);
                        })
                        ->columnSpan(2),

                    // -- Terapis ----------------------------------------------------
                    Select::make('therapist_id')
                        ->label('Terapis')
                        ->relationship('therapist', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->columnSpan(1),

                    // -- Hidden fields ----------------------------------------------
                    Hidden::make('item_name')->dehydrated(),
                    Hidden::make('fee_amount')->dehydrated()->default(0),

                    // -- Quantity ---------------------------------------------------
                    TextInput::make('quantity')
                        ->label('Qty')
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                            $price    = (float) ($get('price') ?? 0);
                            $qty      = max(1, (int) $state);
                            $subtotal = $price * $qty;

                            $set('subtotal', $subtotal);
                            static::recalculateTotals($get, $set);
                        })
                        ->columnSpan(1),

                    // -- Harga (readonly, diisi otomatis) ---------------------------
                    TextInput::make('price')
                        ->label('Harga')
                        ->numeric()
                        ->prefix('Rp')
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->columnSpan(1),

                    // -- Subtotal (readonly, dihitung otomatis) ---------------------
                    TextInput::make('subtotal')
                        ->label('Subtotal')
                        ->numeric()
                        ->prefix('Rp')
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->columnSpan(1),
                ])
                ->columns(7)
                ->defaultItems(1)
                ->addActionLabel('Tambah Item')
                ->deleteAction(
                    fn($action) => $action->after(function (Get $get, Set $set) {
                        static::recalculateTotals($get, $set);
                    })
                )
                ->live()
                ->columnSpanFull()
                ->required(),

            // ── Ringkasan Pembayaran ──────────────────────────────────────────
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

                    Select::make('discount_id')
                        ->label('Diskon')
                        ->relationship('discount', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            static::recalculateTotals($get, $set);
                        }),

                    TextInput::make('discount_amount')
                        ->label('Potongan Diskon')
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
                        ->extraAttributes(['class' => 'text-xl font-bold']),

                    Hidden::make('subtotal')
                        ->dehydrated()
                        ->default(0),
                ])
                ->columnSpanFull(),

            // ── Metode Pembayaran ─────────────────────────────────────────────
            Radio::make('payment_method')
                ->label('Metode Pembayaran')
                ->options([
                    'cash'     => 'Cash',
                    'qris'     => 'QRIS',
                    'transfer' => 'Transfer',
                ])
                ->default('cash')
                ->inline()
                ->required(),
        ]);
    }

    // =========================================================================
    // TABLE
    // =========================================================================

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
                    ->limit(25),

                TextColumn::make('transactionDetails.item_name')
                    ->label('Item')
                    ->searchable()
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

                SelectFilter::make('item_type')
                    ->label('Tipe Transaksi')
                    ->relationship('transactionDetails', 'item_type')
                    ->options([
                        'normal'  => 'Normal',
                        'member'  => 'Member',
                        'package' => 'Paket',
                    ]),
            ], layout: FiltersLayout::AboveContent)

            ->recordActions([
                ActionGroup::make([
                    PrintReceiptAction::printWithFormat('thermal'),
                    PrintReceiptAction::printWithFormat('a4'),
                    PrintReceiptAction::printWithFormat('dotmatrix'),
                ])->icon(Heroicon::Printer)->color('gray')->label('Cetak Struk'),

                ActionGroup::make(TransactionActions::cashierActions())
                    ->label(' ')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->color('primary'),

                ViewAction::make()->recordTitleAttribute('transaction_code'),
                EditAction::make()->recordTitleAttribute('transaction_code'),
                DeleteAction::make(),
            ])

            ->toolbarActions([]);
    }

    // =========================================================================
    // PAGES
    // =========================================================================

    public static function getPages(): array
    {
        return [
            'index' => ManageTransactions::route('/'),
        ];
    }
}

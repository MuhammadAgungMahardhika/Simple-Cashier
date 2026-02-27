<?php

namespace App\Filament\Resources\Transactions;

use App\Filament\Resources\TransactionResource\Actions\PrintReceiptAction;
use App\Filament\Resources\Transactions\Actions\TransactionActions;
use App\Filament\Resources\Transactions\Pages\ManageTransactions;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Enums\TransactionStatusEnum;
use App\Models\Package;
use App\Models\Service;
use App\Models\Transaction;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
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

    protected static function isCustomerMember(?int $customerId): bool
    {
        if (!$customerId) return false;

        $customer = Customer::find($customerId);

        if (!$customer || !$customer->member_expired_at) return false;

        return \Carbon\Carbon::parse($customer->member_expired_at)->gte(now()->startOfDay());
    }

    protected static function resolveItemType(bool $isMember, bool $isPackage): string
    {
        if ($isPackage) return 'package';
        return $isMember ? 'member' : 'normal';
    }

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
                'fee_amount' => (float) ($isMember
                    ? ($service->member_fee ?? 0)
                    : ($service->fee ?? 0)),
            ],
            default => [
                'price'      => (float) $service->price,
                'fee_amount' => (float) ($service->fee ?? 0),
            ],
        };
    }

    /**
     * Hitung ulang total dari ROOT form.
     *
     * Selalu dipanggil dengan $get & $set yang sudah di-scope ke ROOT.
     * Dari dalam repeater: gunakan recalculateTotalsFromRow().
     *
     * Sum pakai price * quantity agar tidak bergantung pada field `subtotal`
     * yang mungkin belum ter-commit ke state saat fungsi ini dipanggil.
     */
    protected static function recalculateTotals(Get $get, Set $set): void
    {
        $details  = collect($get('transactionDetails') ?? []);

        $subtotal = $details->sum(
            fn($item) => (float) ($item['price'] ?? 0) * max(1, (int) ($item['quantity'] ?? 1))
        );

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

        $set('discount_amount',      $discountAmount);
        $set('total_after_discount', $subtotal - $discountAmount);
    }

    /**
     * Versi recalculate yang dipanggil dari DALAM repeater row.
     *
     * Masalah: dari dalam row, $get('transactionDetails') tidak bisa diakses.
     * Path yang benar adalah $get('../../transactionDetails').
     *
     * Selain itu, row yang sedang diedit belum tentu ter-commit ke state
     * sehingga kita inject nilai price & qty row tersebut secara eksplisit
     * via $currentPrice & $currentQty.
     *
     * Cara kerja:
     * 1. Baca semua rows via path ../../transactionDetails
     * 2. Sum price * qty dari semua row (state lama)
     * 3. Koreksi dengan mengurangi subtotal row aktif (state lama) lalu
     *    menambah subtotal row aktif yang baru ($currentPrice * $currentQty)
     *
     * Ini tidak butuh $uuid dan tidak ada injection yang tidak bisa di-resolve.
     */
    protected static function recalculateTotalsFromRow(
        Get   $get,
        Set   $set,
        float $currentPrice,
        int   $currentQty
    ): void {
        $details = collect($get('../../transactionDetails') ?? []);

        // Sum semua rows dari state (termasuk row yang sedang diedit — nilainya masih lama)
        $subtotalFromState = $details->sum(
            fn($item) => (float) ($item['price'] ?? 0) * max(1, (int) ($item['quantity'] ?? 1))
        );

        // Koreksi: kurangi kontribusi row aktif dari state, tambah nilai barunya
        $oldRowPrice = (float) ($get('price') ?? 0);
        $oldRowQty   = max(1, (int) ($get('quantity') ?? 1));

        $subtotal = $subtotalFromState
            - ($oldRowPrice * $oldRowQty)   // hapus nilai lama row ini
            + ($currentPrice * $currentQty); // tambah nilai baru row ini

        $set('../../subtotal',              $subtotal);
        $set('../../total_before_discount', $subtotal);

        $discountId     = $get('../../discount_id');
        $discountAmount = 0;

        if ($discountId) {
            $discount = Discount::find($discountId);
            if ($discount) {
                $discountAmount = $discount->type === 'percentage'
                    ? $subtotal * ($discount->value / 100)
                    : (float) $discount->value;
            }
        }

        $set('../../discount_amount',      $discountAmount);
        $set('../../total_after_discount', $subtotal - $discountAmount);
    }

    protected static function buildDetailRow(
        Service $service,
        string  $itemType,
        bool    $isMember,
        int     $qty       = 1,
        ?int    $packageId = null
    ): array {
        ['price' => $price, 'fee_amount' => $fee] =
            static::resolvePrice($service, $itemType, $isMember);

        return [
            'service_id'   => $service->id,
            'package_id'   => $packageId,
            'therapist_id' => null,
            'item_type'    => $itemType,
            'item_name'    => $service->name,
            'quantity'     => $qty,
            'price'        => $price,
            'fee_amount'   => $fee,
            'subtotal'     => $price * $qty,
        ];
    }

    // =========================================================================
    // FORM
    // =========================================================================

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Hidden::make('transaction_code')
                ->default(fn() => 'TRX-' . date('Ymd') . '-' . strtoupper(uniqid()))
                ->dehydrated(),

            Hidden::make('transaction_date')
                ->default(now()->toDateString())
                ->dehydrated(),

            Hidden::make('status')
                ->default(TransactionStatusEnum::Pending->value)
                ->dehydrated(),

            // ── Customer ──────────────────────────────────────────────────────
            Select::make('customer_id')
                ->label('Nama Pelanggan')
                ->relationship('customer', 'name')
                ->getOptionLabelFromRecordUsing(
                    fn($record) => "{$record->code} - {$record->name} ({$record->phone})"
                )
                ->searchable(['code', 'name', 'phone'])
                ->preload()
                ->required()
                ->live()
                ->columnSpanFull()
                ->helperText(function (Get $get) {
                    $customerId = $get('customer_id');
                    if (!$customerId) return null;

                    $customer = Customer::find($customerId);
                    if (!$customer || !$customer->member_expired_at) {
                        return '👤 Customer reguler (bukan member)';
                    }

                    $expired = \Carbon\Carbon::parse($customer->member_expired_at);
                    return $expired->gte(now()->startOfDay())
                        ? '🟢 Member aktif — berlaku hingga ' . $expired->format('d/m/Y')
                        : '🔴 Member sudah expired sejak ' . $expired->format('d/m/Y');
                })
                ->afterStateUpdated(function (Get $get, Set $set) {
                    // Rebuild semua row harga sesuai status member customer baru
                    $customerId = $get('customer_id');
                    $isMember   = static::isCustomerMember($customerId);
                    $details    = $get('transactionDetails') ?? [];

                    $updated = [];
                    foreach ($details as $key => $row) {
                        $serviceId = $row['service_id'] ?? null;
                        $packageId = $row['package_id'] ?? null;
                        $qty       = max(1, (int) ($row['quantity'] ?? 1));

                        if (!$serviceId) {
                            $updated[$key] = $row;
                            continue;
                        }

                        $service = Service::find($serviceId);
                        if (!$service) {
                            $updated[$key] = $row;
                            continue;
                        }

                        $itemType = static::resolveItemType($isMember, !is_null($packageId));

                        ['price' => $price, 'fee_amount' => $fee] =
                            static::resolvePrice($service, $itemType, $isMember);

                        $updated[$key] = array_merge($row, [
                            'item_type'  => $itemType,
                            'price'      => $price,
                            'fee_amount' => $fee,
                            'subtotal'   => $price * $qty,
                        ]);
                    }

                    $set('transactionDetails', $updated);

                    // Dipanggil dari ROOT — pakai recalculateTotals biasa
                    static::recalculateTotals($get, $set);
                })
                ->createOptionForm([
                    Grid::make()->columns(2)->schema([
                        TextInput::make('name')->label('Nama Pelanggan')->required(),
                        TextInput::make('phone')->label('Nomor Telepon/WA')->tel()->required(),
                        TextInput::make('birth_place')
                            ->label('Tempat Lahir'),
                        DatePicker::make('birth_date')
                            ->label('Tanggal Lahir')
                            ->date('d-m-Y'),
                        TextInput::make('email')->label('Email')->email(),
                        Textarea::make('address')->label('Alamat'),
                    ]),
                ])
                ->createOptionModalHeading('Tambah Pelanggan Baru')
                ->editOptionForm([
                    Grid::make()->columns(2)->schema([
                        TextInput::make('code')->label('Kode Pelanggan')->disabled()->dehydrated()->required(),
                        TextInput::make('name')->label('Nama Pelanggan')->required(),
                        TextInput::make('phone')->label('Nomor Telepon/WA')->tel()->required(),
                        TextInput::make('birth_place')
                            ->label('Tempat Lahir'),
                        DatePicker::make('birth_date')
                            ->label('Tanggal Lahir')
                            ->date('d-m-Y'),
                        TextInput::make('email')->label('Email')->email(),
                        Textarea::make('address')->label('Alamat'),


                        Placeholder::make('membership_status')
                            ->label('Status Member')
                            ->content(function ($record) {
                                if (!$record || !$record->member_expired_at) {
                                    return 'Non Member';
                                }

                                return now()->lte($record->member_expired_at)
                                    ? 'Aktif sampai ' . $record->member_expired_at->format('d M Y')
                                    : 'Expired pada ' . $record->member_expired_at->format('d M Y');
                            })->columnSpanFull(),

                        DatePicker::make('member_started_at')
                            ->label('Tanggal Mulai')
                            ->disabled(),

                        DatePicker::make('member_expired_at')
                            ->label('Tanggal Berakhir')
                            ->disabled(),

                        Action::make('extendMembership')
                            ->label('Bayar / Perpanjang 1 Bulan')
                            ->action(function ($record) {

                                if (!$record) return;

                                $now = now();

                                // Kalau masih aktif → tambahkan dari expired terakhir
                                if ($record->member_expired_at && $now->lte($record->member_expired_at)) {
                                    $newExpired = $record->member_expired_at->copy()->addMonth();
                                } else {
                                    // Kalau belum pernah atau sudah expired
                                    $record->member_started_at = $now;
                                    $newExpired = $now->copy()->addMonth();
                                }

                                $record->member_expired_at = $newExpired;
                                $record->save();
                            }),
                        Action::make('decreaseMembership')
                            ->label('Kurangi 1 Bulan')
                            ->color('danger')
                            ->action(function ($record) {

                                if (!$record->member_expired_at) {
                                    return;
                                }

                                $now = now();
                                $newExpired = $record->member_expired_at->copy()->subMonth();

                                // Jangan boleh kurang dari hari ini
                                if ($newExpired->lt($now)) {
                                    $newExpired = $now;
                                }

                                $record->member_expired_at = $newExpired;
                                $record->save();
                            }),

                    ]),
                ])
                ->editOptionModalHeading('Ubah Data Pelanggan'),

            // ── Pilih Paket ───────────────────────────────────────────────────
            Section::make('Tambah dari Paket')
                ->description('Pilih paket untuk menambahkan semua layanan dalam paket sekaligus ke detail transaksi.')
                ->schema([
                    Select::make('_package_picker')
                        ->label('Pilih Paket')
                        ->options(
                            Package::where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                        )
                        ->searchable()
                        ->nullable()
                        ->placeholder('— Pilih paket untuk expand —')
                        ->live()
                        ->dehydrated(false)
                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                            if (!$state) return;

                            $package = Package::with('packageDetails.service')->find($state);
                            if (!$package) return;

                            $customerId  = $get('customer_id');
                            $isMember    = static::isCustomerMember($customerId);
                            $currentRows = collect($get('transactionDetails') ?? [])
                                ->filter(fn($row) => !empty($row['service_id']))
                                ->values()
                                ->toArray();

                            $newRows = [];
                            foreach ($package->packageDetails as $detail) {
                                $service = $detail->service;
                                if (!$service || !$service->is_active) continue;

                                $newRows[] = static::buildDetailRow(
                                    service: $service,
                                    itemType: 'package',
                                    isMember: $isMember,
                                    qty: 1,
                                    packageId: $package->id,
                                );
                            }

                            $set('transactionDetails', array_merge($currentRows, $newRows));
                            $set('_package_picker', null);

                            // Dipanggil dari ROOT
                            static::recalculateTotals($get, $set);
                        }),
                ])
                ->columnSpanFull()
                ->collapsed(),

            // ── Transaction Details (Repeater) ────────────────────────────────
            Repeater::make('transactionDetails')
                ->relationship()
                ->label('Detail Transaksi')
                ->table([
                    TableColumn::make('Tipe'),
                    TableColumn::make('Layanan'),
                    TableColumn::make('Terapis'),
                    TableColumn::make('Qty'),
                    TableColumn::make('Harga'),
                    TableColumn::make('Subtotal'),
                ])
                ->schema([

                    TextInput::make('item_type')
                        ->label('Tipe')
                        ->disabled()
                        ->dehydrated()
                        ->default('normal')
                        ->columnSpan(1),

                    Select::make('service_id')
                        ->label('Layanan')
                        ->options(
                            Service::where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                        )
                        ->searchable()
                        ->nullable()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                            if (!$state) {
                                $set('item_name',  null);
                                $set('price',      0);
                                $set('fee_amount', 0);
                                $set('subtotal',   0);
                                // Row ini price=0, qty apapun → subtotal 0
                                static::recalculateTotalsFromRow($get, $set, 0, 1);
                                return;
                            }

                            $service = Service::find($state);
                            if (!$service) return;

                            $customerId = $get('../../customer_id');
                            $isMember   = static::isCustomerMember($customerId);
                            $packageId  = $get('package_id');
                            $itemType   = static::resolveItemType($isMember, !is_null($packageId));
                            $qty        = max(1, (int) ($get('quantity') ?? 1));

                            ['price' => $price, 'fee_amount' => $fee] =
                                static::resolvePrice($service, $itemType, $isMember);

                            $set('item_type',  $itemType);
                            $set('item_name',  $service->name);
                            $set('price',      $price);
                            $set('fee_amount', $fee);
                            $set('subtotal',   $price * $qty);

                            // Inject price & qty baru agar koreksi akurat
                            static::recalculateTotalsFromRow($get, $set, $price, $qty);
                        })
                        ->columnSpan(2),

                    Hidden::make('package_id')->dehydrated(),

                    Select::make('therapist_id')
                        ->label('Terapis')
                        ->relationship('therapist', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->columnSpan(2),

                    Hidden::make('item_name')->dehydrated(),
                    Hidden::make('fee_amount')->dehydrated()->default(0),

                    TextInput::make('quantity')
                        ->label('Qty')
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                            $price = (float) ($get('price') ?? 0);
                            $qty   = max(1, (int) $state);

                            $set('subtotal', $price * $qty);

                            // Inject price (sudah benar di state) & qty baru
                            static::recalculateTotalsFromRow($get, $set, $price, $qty);
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
                ->columns(8)
                ->defaultItems(1)
                ->addActionLabel('Tambah Layanan')
                ->deleteAction(
                    fn($action) => $action->after(function (Get $get, Set $set) {
                        // Delete dipanggil dari ROOT scope repeater
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
                            // Dipanggil dari ROOT
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
                ])
                ->columnSpanFull(),

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
                    ->description(function ($record) {
                        $exp = $record->customer?->member_expired_at;
                        if (!$exp) return null;

                        return \Carbon\Carbon::parse($exp)->gte(now()->startOfDay())
                            ? '🟢 Member s/d ' . \Carbon\Carbon::parse($exp)->format('d/m/Y')
                            : '🔴 Member expired';
                    })
                    ->limit(30),

                TextColumn::make('transactionDetails.item_name')
                    ->label('Layanan')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList(),

                TextColumn::make('therapists.name')
                    ->label('Terapis')
                    ->searchable()
                    ->sortable()
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),


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
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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

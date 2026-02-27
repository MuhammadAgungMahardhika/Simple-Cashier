<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages\ManageCustomers;
use App\Models\Customer;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static ?string $navigationLabel = 'Pelanggan';
    protected static ?string $label = 'Pelanggan';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Users;
    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Kode Pelanggan')
                    ->disabledOn('create')
                    ->default(fn() => Customer::generateCustomerCode())
                    ->dehydrated()
                    ->required(),

                TextInput::make('name')
                    ->label('Nama Pelanggan')
                    ->required(),
                TextInput::make('phone')
                    ->label('Nomor Telepon / WA')
                    ->tel()
                    ->required(),

                TextInput::make('birth_place')
                    ->label('Tempat Lahir'),

                DatePicker::make('birth_date')
                    ->label('Tanggal Lahir')
                    ->date('d-m-Y'),

                TextInput::make('email')
                    ->label('Email')
                    ->email(),

                Textarea::make('address')
                    ->label('Alamat'),

                Section::make('Membership')
                    ->schema([

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
                    ])
                    ->columnSpanFull()->visibleOn(['edit'])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode Pelanggan')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nama Pelanggan')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('is_member')
                    ->label('Status Member')
                    // ->searchable()
                    ->formatStateUsing(fn($state) => $state ? 'Member' : 'Non-Member')
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'danger'),
                TextColumn::make('member_started_at')
                    ->label('Tanggal Mulai Member')
                    ->date()
                    ->sortable(),
                TextColumn::make('member_expired_at')
                    ->label('Masa Berlaku Member')
                    ->date('d-m-Y')
                    ->sortable(),
                TextColumn::make('birth_place')->label('Tempat Lahir')->searchable(),
                TextColumn::make('birth_date')
                    ->label('Tanggal Lahir')
                    ->date('d-m-Y')
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Nomor Telepon/Wa')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('address')
                    ->label('Alamat')
                    ->searchable(),
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
                Filter::make('is_member ')
                    ->label('Status Member')
                    ->query(fn($query) => $query->whereNotNull('member_expired_at')->where('member_expired_at', '>=', now())),
                Filter::make('is_non_member')
                    ->label('Non-Member')
                    ->query(fn($query) => $query->whereNull('member_expired_at')->orWhere('member_expired_at', '<', now())),


            ], layout: FiltersLayout::AboveContent)
            ->recordActions([
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
            'index' => ManageCustomers::route('/'),
        ];
    }
}

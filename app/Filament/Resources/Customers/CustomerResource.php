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
                    ->readOnly()
                    ->visibleOn(['edit'])
                    ->required(),

                TextInput::make('name')
                    ->label('Nama Pelanggan')
                    ->required(),

                TextInput::make('phone')
                    ->label('Nomor Telepon / WA')
                    ->tel()
                    ->required(),

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
                                    $newExpired = $record->member_expired_at->addMonth();
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
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nama Pelanggan')
                    ->searchable(),
                TextColumn::make('is_member')
                    ->label('Status Member')
                    ->searchable()
                    ->formatStateUsing(fn($state) => $state ? 'Member' : 'Non-Member')
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'danger'),
                TextColumn::make('member_started_at')
                    ->label('Tanggal Mulai Member')
                    ->date()
                    ->sortable(),
                TextColumn::make('member_expired_at')
                    ->label('Masa Berlaku Member')
                    ->date()
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
                //
            ])
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

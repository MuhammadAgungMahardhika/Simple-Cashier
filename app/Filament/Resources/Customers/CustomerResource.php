<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages\ManageCustomers;
use App\Models\Customer;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static ?string $navigationLabel = 'Pelanggan';
    protected static ?string $label = 'Pelanggan';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Users;

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
                    ->label('Nomor Telepon/Wa')
                    ->tel(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                Textarea::make('address'),

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

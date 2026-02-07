<?php

namespace App\Filament\Resources\Services;

use App\Filament\Resources\Services\Pages\ManageServices;
use App\Models\Service;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;
    protected static ?string $navigationLabel = 'Layanan';
    protected static ?string $label = 'Layanan';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::HandRaised;
    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Layanan')
                    ->required(),
                TextInput::make('price')
                    ->label('Harga Layanan')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                TextInput::make('duration')
                    ->label('Durasi (menit)')
                    ->numeric(),
                Toggle::make('is_active')
                    ->label('Aktif?')
                    ->default(true)
                    ->required(),
                Textarea::make('description')
                    ->label('Deskripsi Layanan')
                    ->columnSpanFull(),

                Radio::make('type')
                    ->label('Tipe Fee')
                    ->options([
                        'percentage' => 'Percentage',
                        'fixed' => 'Fixed',
                    ])
                    ->required()
                    ->reactive(),

                TextInput::make('fee')
                    ->label('Fee Layanan')
                    ->required()
                    ->numeric()
                    ->reactive()
                    ->rules(fn(Get $get) => match ($get('type')) {
                        'percentage' => ['min:1', 'max:100'],
                        'fixed' => ['min:1'],
                        default => [],
                    })
                    ->helperText(
                        fn(Get $get) =>
                        $get('type') === 'percentage'
                            ? 'Masukkan angka 1–100'
                            : 'Masukkan nominal rupiah'
                    )
                    ->suffix(
                        fn(Get $get) =>
                        $get('type') === 'percentage'
                            ? '%'
                            : 'Rp'
                    ),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Layanan')
                    ->searchable(),
                TextColumn::make('duration')
                    ->label('Durasi (menit)')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('price')
                    ->label('Harga Layanan')
                    ->alignEnd()
                    ->money('idr')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipe Fee')
                    ->badge()
                    ->formatStateUsing(fn($state) => ucfirst($state))
                    ->color(fn($state) => match ($state) {
                        'percentage' => 'warning',
                        'fixed' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('fee')
                    ->label('Fee Layanan')
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        return $record->type === 'percentage'
                            ? "{$state}%"
                            : 'Rp ' . number_format($state, 0, ',', '.');
                    }),
                IconColumn::make('is_active')
                    ->label('Aktif?')
                    ->boolean(),
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
            'index' => ManageServices::route('/'),
        ];
    }
}

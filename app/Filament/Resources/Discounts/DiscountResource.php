<?php

namespace App\Filament\Resources\Discounts;

use App\Filament\Resources\Discounts\Pages\ManageDiscounts;
use App\Models\Discount;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;
    protected static ?string $navigationLabel = 'Diskon';
    protected static ?string $label = 'Diskon';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Tag;
    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Diskon')
                    ->required(),
                Select::make('type')
                    ->label('Tipe Diskon')
                    ->options([
                        'percentage' => 'Percentage',
                        'fixed' => 'Fixed',
                    ])
                    ->required()
                    ->reactive(),

                TextInput::make('value')
                    ->label('Nilai Diskon')
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
                    ->label('Nama Diskon')
                    ->searchable(),

                TextColumn::make('type')
                    ->label('Tipe Diskon')
                    ->badge()
                    ->formatStateUsing(fn($state) => ucfirst($state))
                    ->color(fn($state) => match ($state) {
                        'percentage' => 'warning',
                        'fixed' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('value')
                    ->label('Nilai Diskon')
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        return $record->type === 'percentage'
                            ? "{$state}%"
                            : 'Rp ' . number_format($state, 0, ',', '.');
                    }),
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
            'index' => ManageDiscounts::route('/'),
        ];
    }
}

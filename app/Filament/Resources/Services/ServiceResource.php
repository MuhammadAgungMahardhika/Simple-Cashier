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
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
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
                Select::make('service_group_id')
                    ->label('Grup Layanan')
                    ->relationship('serviceGroup', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->label('Nama Layanan')
                    ->required(),
                TextInput::make('duration')
                    ->label('Durasi (menit)')
                    ->numeric(),
                Section::make([
                    TextInput::make('price')
                        ->label('Harga Umum')
                        ->required()
                        ->numeric()
                        ->prefix('Rp'),
                    TextInput::make('member_price')
                        ->label('Harga Member')
                        ->required()
                        ->numeric()
                        ->prefix('Rp'),
                    TextInput::make('package_price')
                        ->label('Harga Paket Umum')
                        ->required()
                        ->numeric()
                        ->prefix('Rp'),
                    TextInput::make('member_package_price')
                        ->label('Harga Paket Member')
                        ->required()
                        ->numeric()
                        ->prefix('Rp'),
                ])->columnSpanFull()->columns(4)->heading('Harga Layanan'),
                Section::make([
                    TextInput::make('fee')
                        ->label('Fee Umum')
                        ->required()
                        ->numeric()
                        ->prefix('Rp'),

                    TextInput::make('member_fee')
                        ->label('Fee Member')
                        ->required()
                        ->numeric()
                        ->prefix('Rp'),

                ])->columnSpanFull()->columns(2)->heading('Fee Layanan'),

                Toggle::make('is_active')
                    ->label('Aktif?')
                    ->default(true)
                    ->required(),
                Textarea::make('description')
                    ->label('Deskripsi Layanan')
                    ->columnSpanFull(),


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->defaultGroup('serviceGroup.name')
            ->groups([
                Group::make('serviceGroup.name')->label('Group')->collapsible()->titlePrefixedWithLabel(false),
            ])
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
                    ->label('Harga Umum')
                    ->alignEnd()
                    ->money('idr')
                    ->sortable(),
                TextColumn::make('member_price')
                    ->label('Harga Member')
                    ->alignEnd()
                    ->money('idr')
                    ->sortable(),
                TextColumn::make('package_price')
                    ->label('Harga Paket Umum')
                    ->alignEnd()
                    ->money('idr')
                    ->sortable(),
                TextColumn::make('member_package_price')
                    ->label('Harga Paket Member')
                    ->alignEnd()
                    ->money('idr')
                    ->sortable(),

                TextColumn::make('fee')
                    ->label('Fee Layanan')
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        return $record->type === 'percentage'
                            ? "{$state}%"
                            : 'Rp ' . number_format($state, 0, ',', '.');
                    }),
                TextColumn::make('member_fee')
                    ->label('Fee Member Layanan')
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
                SelectFilter::make('service_group_id')
                    ->label('Grup Layanan')
                    ->relationship('serviceGroup', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('is_active')
                    ->label('Aktif?')
                    ->options([
                        1 => 'Ya',
                        0 => 'Tidak',
                    ]),
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
            'index' => ManageServices::route('/'),
        ];
    }
}

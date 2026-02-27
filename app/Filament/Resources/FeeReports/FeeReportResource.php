<?php

namespace App\Filament\Resources\FeeReports;

use App\Filament\Resources\FeeReports\Pages\CreateFeeReport;
use App\Filament\Resources\FeeReports\Pages\EditFeeReport;
use App\Filament\Resources\FeeReports\Pages\ListFeeReports;
use App\Filament\Resources\FeeReports\Schemas\FeeReportForm;
use App\Filament\Resources\FeeReports\Tables\FeeReportsTable;
use App\Models\Enums\TransactionStatusEnum;
use App\Models\FeeReport;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Policies\TransactionPolicy;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;


class FeeReportResource extends Resource
{
    protected static ?string $model = TransactionDetail::class;
    protected static ?string $navigationLabel = 'Fee Terapis';
    protected static ?string $label = 'Fee Terapis';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentCheck;
    protected static string|UnitEnum|null $navigationGroup = 'Laporan';


    public static function table(Table $table): Table
    {
        return $table
            ->groups([
                Group::make('therapist.name')->label('Terapis')->collapsible()->titlePrefixedWithLabel(false),
            ])
            ->modifyQueryUsing(
                fn(Builder $query) => $query
                    ->whereHas('transaction', fn(Builder $q) => $q->where('status', TransactionStatusEnum::Paid->value))
                    ->where('therapist_id', '!=', null)
            )
            ->defaultGroup('therapist.name')
            ->defaultSort('transaction.transaction_date', 'desc')
            ->columns([
                TextColumn::make('transaction.transaction_date')
                    ->label('Tanggal')
                    ->date('d-m-Y')
                    ->sortable(),
                TextColumn::make('item_name')
                    ->label('Layanan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('item_type')
                    ->label('Tipe Layanan')
                    ->searchable()
                    ->sortable()
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
                TextColumn::make('package.name')
                    ->label('Paket')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn($state) => $state ?? '-'),
                TextColumn::make('fee_amount')
                    ->label('Fee Terapis')
                    ->size(TextSize::Medium)
                    ->weight(FontWeight::Bold)
                    ->color('success')
                    ->numeric()
                    ->alignEnd()
                    ->money('IDR'),

            ])

            ->filters([

                Filter::make('transaction_date')
                    ->schema([
                        DatePicker::make('from')->label('Dari Tanggal')->default(now()->startOfDay()),
                        DatePicker::make('to')->label('Sampai Tanggal')->default(now()->endOfDay()),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['from']) {
                            $query->whereHas('transaction', fn(Builder $q) => $q->whereDate('transaction_date', '>=', $data['from']));
                        }
                        if ($data['to']) {
                            $query->whereHas('transaction', fn(Builder $q) => $q->whereDate('transaction_date', '<=', $data['to']));
                        }
                    })->columnSpan(2)->columns(2),
                SelectFilter::make('therapist_id')
                    ->label('Terapis')
                    ->relationship('therapist', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('item_type')
                    ->label('Tipe Layanan')
                    ->options([
                        'member'  => 'Member',
                        'package' => 'Paket',
                        'normal'  => 'Normal',
                    ])
                    ->searchable()
                    ->multiple(),

                SelectFilter::make('transaction.status')
                    ->label('Status')
                    ->options(TransactionStatusEnum::labels()),
            ], layout: FiltersLayout::AboveContent)

            ->recordActions([])
            ->toolbarActions([]);
    }


    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeeReports::route('/'),

        ];
    }
}

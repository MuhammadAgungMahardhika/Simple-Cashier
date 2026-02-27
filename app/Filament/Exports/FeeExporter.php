<?php

namespace App\Filament\Exports;

use App\Models\Fee;
use App\Models\Therapist;
use App\Models\TransactionDetail;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class FeeExporter extends Exporter
{
    protected static ?string $model = TransactionDetail::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('transaction.transaction_date')->label('Tanggal Transaksi'),
            ExportColumn::make('transaction.transaction_code')->label('Kode Transaksi'),
            ExportColumn::make('therapist.name')->label('Nama Terapis'),
            ExportColumn::make('transaction.customer.name')->label('Nama Pelanggan'),
            ExportColumn::make('package.name')->label('Nama Paket'),
            ExportColumn::make('item_name')->label('Nama Layanan'),
            ExportColumn::make('item_type')->label('Tipe Layanan'),
            ExportColumn::make('price')->label('Harga Layanan'),
            ExportColumn::make('quantity')->label('Jumlah Layanan'),
            ExportColumn::make('subtotal')->label('Subtotal Layanan'),
            ExportColumn::make('fee_amount')->label('Fee Terapis'),
            ExportColumn::make('is_active')->label('Aktif?'),
            ExportColumn::make('created_by'),
            ExportColumn::make('updated_by'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }
    public static function modifyQuery(Builder $query): Builder
    {
        return $query
            ->with([
                'therapist',
                'transaction.customer',
                'package',
            ])
            ->orderBy(
                Therapist::select('name')
                    ->whereColumn('therapists.id', 'transaction_details.therapist_id'),
                'asc'
            );
    }
    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your fee export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}

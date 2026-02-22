<?php

namespace App\Filament\Exports;

use App\Models\Transaction;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class TransactionExporter extends Exporter
{
    protected static ?string $model = Transaction::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('transaction_code')->label('Kode Transaksi'),
            ExportColumn::make('discount.name')->label('Diskon'),
            ExportColumn::make('customer.name')->label('Pelanggan'),
            ExportColumn::make('total_before_discount')->label('Total Sebelum Diskon'),
            ExportColumn::make('discount_amount')->label('Jumlah Diskon'),
            ExportColumn::make('total_after_discount')->label('Total Setelah Diskon'),
            ExportColumn::make('transaction_date')->label('Tanggal Transaksi'),
            ExportColumn::make('payment_method')->label('Metode Pembayaran'),
            ExportColumn::make('status'),
            ExportColumn::make('created_by'),
            ExportColumn::make('updated_by'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your transaction export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}

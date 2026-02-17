<?php

namespace App\Filament\Exports;

use App\Models\Service;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class ServiceExporter extends Exporter
{
    protected static ?string $model = Service::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('service_group_id')
                ->label('ID Grup Layanan'),
            ExportColumn::make('code')->label('Kode Layanan'),
            ExportColumn::make('name')->label('Nama Layanan'),
            ExportColumn::make('price')->label('Harga Umum'),
            ExportColumn::make('member_price')->label('Harga Member'),
            ExportColumn::make('package_price')->label('Harga Paket Umum'),
            ExportColumn::make('member_package_price')->label('Harga Paket Member'),
            ExportColumn::make('fee')->label('Fee Umum'),
            ExportColumn::make('member_fee')->label('Fee Member'),
            ExportColumn::make('description')->label('Deskripsi'),
            ExportColumn::make('duration')->label('Durasi (menit)'),
            ExportColumn::make('is_active')->label('Aktif?'),
            ExportColumn::make('created_by'),
            ExportColumn::make('updated_by'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your service export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}

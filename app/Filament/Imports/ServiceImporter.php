<?php

namespace App\Filament\Imports;

use App\Models\Service;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class ServiceImporter extends Importer
{
    protected static ?string $model = Service::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('service_group_id')
                ->label('ID Grup Layanan')
                ->numeric()
                ->rules([''])
                ->label('ID Grup Layanan'),
            ImportColumn::make('code')
                ->requiredMapping()
                ->numeric()
                ->label('Kode Layanan')
                ->rules(['required']),
            ImportColumn::make('name')
                ->label('Nama Layanan')
                ->requiredMapping()
                ->rules(['required', 'max:255']),

            ImportColumn::make('price')
                ->requiredMapping()
                ->label('Harga Umum')
                ->numeric()
                ->rules(['required']),
            ImportColumn::make('member_price')
                ->requiredMapping()
                ->label('Harga Member')
                ->numeric()
                ->rules(['required']),
            ImportColumn::make('package_price')
                ->requiredMapping()
                ->label('Harga Paket Umum')
                ->numeric()
                ->rules(['required']),
            ImportColumn::make('member_package_price')
                ->requiredMapping()
                ->label('Harga Paket Member')
                ->numeric()
                ->rules(['required']),
            ImportColumn::make('fee')
                ->label('Fee Umum')
                ->numeric()
                ->rules([]),
            ImportColumn::make('member_fee')
                ->label('Fee Member')
                ->numeric()
                ->rules([]),
            ImportColumn::make('description')->label('Deskripsi'),
            ImportColumn::make('duration')
                ->label('Durasi (menit)')
                ->numeric()
                ->rules([]),
            ImportColumn::make('is_active')
                ->label('Aktif?')
                ->requiredMapping()
                ->boolean()
                ->rules(['required', 'boolean']),
            ImportColumn::make('created_by')
                ->rules(['max:255']),
            ImportColumn::make('updated_by')
                ->rules(['max:255']),
        ];
    }

    public function resolveRecord(): Service
    {
        return Service::firstOrNew([
            'code' => $this->data['code'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your service import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}

<?php

namespace App\Filament\Resources\TransactionReports\Pages;

use App\Filament\Exports\TransactionExporter;
use App\Filament\Resources\TransactionReports\TransactionReportResource;
use App\Policies\TransactionPolicy;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ListTransactionReports extends ListRecords
{
    protected static string $resource = TransactionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make() // Add the export action
                ->exporter(TransactionExporter::class) // Link to your existing exporter
                ->fileName(fn() => 'data-transaksi-' . now()->format('Ymd_His'))
                ->icon(Heroicon::ArrowDown)
                ->color('success')
                ->authorize(fn() =>  app(TransactionPolicy::class)->export(Auth::user()))
                ->label('Ekspor Data Transaksi'),
        ];
    }
}

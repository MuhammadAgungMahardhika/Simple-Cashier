<?php

namespace App\Filament\Resources\FeeReports\Pages;

use App\Filament\Exports\FeeExporter;
use App\Filament\Resources\FeeReports\FeeReportResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ListFeeReports extends ListRecords
{
    protected static string $resource = FeeReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make() // Add the export action
                ->exporter(FeeExporter::class) // Link to your existing exporter
                ->fileName(fn() => 'data-fee-' . now()->format('Ymd_His'))
                ->icon(Heroicon::ArrowDown)
                ->color('success')
                // ->authorize(fn() =>  app(FeePolicy::class)->export(Auth::user()))
                ->label('Ekspor Data Fee Terapis'),
        ];
    }
}

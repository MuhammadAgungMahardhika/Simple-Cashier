<?php

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Exports\ServiceExporter;
use App\Filament\Imports\ServiceImporter;
use App\Filament\Resources\Services\ServiceResource;
use App\Policies\ServicePolicy;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ManageServices extends ManageRecords
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportAction::make() // Add the export action
                ->exporter(ServiceExporter::class) // Link to your existing exporter
                ->fileName(fn() => 'data-layanan-' . now()->format('Ymd_His'))
                ->icon(Heroicon::ArrowDown)
                ->color('success')
                ->authorize(fn() =>  app(ServicePolicy::class)->export(Auth::user()))
                ->label('Ekspor Data Layanan'),
            ImportAction::make()
                ->importer(ServiceImporter::class)->label('Impor Data Layanan')
                ->authorize(fn() =>  app(ServicePolicy::class)->import(Auth::user()))
                ->icon(Heroicon::ArrowDown),

        ];
    }
}

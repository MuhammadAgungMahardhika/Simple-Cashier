<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Exports\CustomerExporter;
use App\Filament\Resources\Customers\CustomerResource;
use App\Policies\CustomerPolicy;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ManageCustomers extends ManageRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportAction::make() // Add the export action
                ->exporter(CustomerExporter::class) // Link to your existing exporter
                ->fileName(fn() => 'data-pelanggan-' . now()->format('Ymd_His'))
                ->icon(Heroicon::ArrowDown)
                ->color('success')
                ->authorize(fn() =>  app(CustomerPolicy::class)->export(Auth::user()))
                ->label('Ekspor Data Pelanggan'),
        ];
    }
}

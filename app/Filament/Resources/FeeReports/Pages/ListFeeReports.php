<?php

namespace App\Filament\Resources\FeeReports\Pages;

use App\Filament\Resources\FeeReports\FeeReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFeeReports extends ListRecords
{
    protected static string $resource = FeeReportResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

<?php

namespace App\Filament\Resources\TransactionReports\Pages;

use App\Filament\Resources\TransactionReports\TransactionReportResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTransactionReport extends ViewRecord
{
    protected static string $resource = TransactionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

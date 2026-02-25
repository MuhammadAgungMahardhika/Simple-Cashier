<?php

namespace App\Filament\Resources\FeeReports\Pages;

use App\Filament\Resources\FeeReports\FeeReportResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFeeReport extends EditRecord
{
    protected static string $resource = FeeReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

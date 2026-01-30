<?php

namespace App\Filament\Resources\TransactionResource\Actions;

use Filament\Actions\Action;
use Illuminate\Support\Facades\Blade;

class PrintReceiptAction
{
    public static function make(): Action
    {
        return Action::make('print')
            ->label('Print')
            ->icon('heroicon-o-printer')
            ->color('success')
            ->url(fn($record) => route('transaction.print', $record->id))
            ->openUrlInNewTab();
    }
}

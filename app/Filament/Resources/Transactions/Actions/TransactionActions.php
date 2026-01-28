<?php

namespace App\Filament\Resources\Transactions\Actions;

use App\Models\Transaction;
use Filament\Actions\Action;

class TransactionActions
{
    public static function pay(): Action
    {
        return Action::make('pay')
            ->label('Bayar')
            ->icon('heroicon-o-credit-card')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn(Transaction $record) => $record->status === 'pending')
            ->action(function (Transaction $record) {
                $record->update([
                    'status' => 'paid',
                    'paid_at' => now(), // optional tapi recommended
                ]);
            });
    }

    public static function cancel(): Action
    {
        return Action::make('cancel')
            ->label('Batalkan')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->visible(
                fn(Transaction $record) =>
                in_array($record->status, ['pending', 'unpaid'])
            )
            ->action(
                fn(Transaction $record) =>
                $record->update(['status' => 'cancelled'])
            );
    }

    public static function void(): Action
    {
        return Action::make('void')
            ->label('Void')
            ->icon('heroicon-o-trash')
            ->color('gray')
            ->requiresConfirmation()
            ->visible(fn(Transaction $record) => $record->status === 'paid')
            ->action(
                fn(Transaction $record) =>
                $record->update(['status' => 'cancelled'])
            );
    }

    /**
     * Helper biar tinggal panggil sekali
     */
    public static function all(): array
    {
        return [
            self::pay(),
            self::cancel(),
            self::void(),
        ];
    }
}

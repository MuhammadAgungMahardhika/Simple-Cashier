<?php

namespace App\Filament\Resources\Transactions\Actions;

use App\Models\Enums\TransactionStatusEnum;
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
            ->visible(fn(Transaction $record) => $record->status ===  TransactionStatusEnum::Pending->value)
            ->action(function (Transaction $record) {
                $record->update([
                    'status' => TransactionStatusEnum::Paid->value,
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
                in_array($record->status, [TransactionStatusEnum::Pending->value, TransactionStatusEnum::Unpaid->value])
            )
            ->action(
                fn(Transaction $record) =>
                $record->update(['status' => TransactionStatusEnum::Cancelled->value])
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
        ];
    }
}

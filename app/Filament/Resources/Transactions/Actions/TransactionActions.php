<?php

namespace App\Filament\Resources\Transactions\Actions;

use App\Models\Enums\TransactionStatusEnum;
use App\Models\Transaction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class TransactionActions
{
    /**
     * Action untuk menandai transaksi sebagai PAID (Lunas)
     * Digunakan saat kasir menerima pembayaran penuh
     */

    public static $defaultFormat = 'thermal';
    public static function markAsPaid(): Action
    {
        return Action::make('markAsPaid')
            ->label('Tandai Lunas')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Konfirmasi Pembayaran')
            ->modalDescription('Apakah pembayaran sudah diterima dari pelanggan?')
            ->modalSubmitActionLabel('Ya, Sudah Lunas')
            ->visible(
                fn(Transaction $record) =>
                in_array($record->status, [
                    TransactionStatusEnum::Pending->value,
                    TransactionStatusEnum::Unpaid->value
                ])
            )
            ->action(function (Transaction $record) {
                $record->update([
                    'status' => TransactionStatusEnum::Paid->value,
                ]);

                Notification::make()
                    ->success()
                    ->title('Pembayaran Berhasil')
                    ->body("Transaksi {$record->transaction_code} telah lunas.")
                    ->send();
            });
    }

    /**
     * Action untuk pembayaran langsung + cetak struk
     * Paling sering digunakan kasir
     */
    public static function payAndPrint(): Action
    {
        return Action::make('payAndPrint')
            ->label('Bayar & Cetak')
            ->icon('heroicon-o-printer')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Pembayaran & Cetak Struk')
            ->modalDescription('Pembayaran akan ditandai lunas dan struk akan dicetak.')
            ->modalSubmitActionLabel('Proses & Cetak')
            ->visible(
                fn(Transaction $record) =>
                in_array($record->status, [
                    TransactionStatusEnum::Pending->value,
                    TransactionStatusEnum::Unpaid->value
                ])
            )
            ->action(function (Transaction $record) {
                $record->update([
                    'status' => TransactionStatusEnum::Paid->value,
                ]);

                Notification::make()
                    ->success()
                    ->title('Transaksi Lunas')
                    ->body('Struk siap dicetak')
                    ->send();

                // Redirect ke print thermal
                return redirect()->route('transaction.print.format', [
                    'id' => $record->id,
                    'format' => self::$defaultFormat
                ]);
            });
    }

    /**
     * Action untuk menandai sebagai UNPAID (Belum Lunas)
     * Misalnya untuk transaksi kredit/cicilan
     */
    public static function markAsUnpaid(): Action
    {
        return Action::make('markAsUnpaid')
            ->label('Tandai Belum Lunas')
            ->icon('heroicon-o-clock')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Tandai Belum Lunas')
            ->modalDescription('Transaksi akan ditandai sebagai belum lunas. Pelanggan dapat melakukan pembayaran nanti.')
            ->modalSubmitActionLabel('Ya, Belum Lunas')
            ->visible(
                fn(Transaction $record) =>
                $record->status === TransactionStatusEnum::Pending->value
            )
            ->action(function (Transaction $record) {
                $record->update([
                    'status' => TransactionStatusEnum::Unpaid->value,
                ]);

                Notification::make()
                    ->warning()
                    ->title('Status Diperbarui')
                    ->body("Transaksi {$record->transaction_code} ditandai belum lunas.")
                    ->send();
            });
    }

    /**
     * Action untuk membatalkan transaksi
     */
    public static function cancel(): Action
    {
        return Action::make('cancel')
            ->label('Batalkan')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Batalkan Transaksi?')
            ->modalDescription('Transaksi yang dibatalkan tidak dapat dikembalikan.')
            ->modalSubmitActionLabel('Ya, Batalkan')
            ->visible(
                fn(Transaction $record) =>
                in_array($record->status, [
                    TransactionStatusEnum::Pending->value,
                    TransactionStatusEnum::Unpaid->value
                ])
            )
            ->action(function (Transaction $record) {
                $record->update([
                    'status' => TransactionStatusEnum::Cancelled->value,
                ]);

                Notification::make()
                    ->warning()
                    ->title('Transaksi Dibatalkan')
                    ->body("Transaksi {$record->transaction_code} telah dibatalkan.")
                    ->send();
            });
    }



    /**
     * Action untuk mengubah kembali ke Pending
     * Jika ada kesalahan penandaan status
     */
    public static function backToPending(): Action
    {
        return Action::make('backToPending')
            ->label('Kembalikan ke Pending')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Kembalikan ke Pending?')
            ->modalDescription('Status transaksi akan dikembalikan ke Pending.')
            ->visible(
                fn(Transaction $record) =>
                in_array($record->status, [
                    TransactionStatusEnum::Unpaid->value,
                    TransactionStatusEnum::Cancelled->value
                ])
            )
            ->action(function (Transaction $record) {
                $record->update([
                    'status' => TransactionStatusEnum::Pending->value,
                ]);

                Notification::make()
                    ->info()
                    ->title('Status Dikembalikan')
                    ->body("Transaksi {$record->transaction_code} dikembalikan ke Pending.")
                    ->send();
            });
    }

    /**
     * Helper untuk semua actions
     */
    public static function all(): array
    {
        return [
            self::payAndPrint(),
            self::markAsPaid(),
            self::markAsUnpaid(),
            self::cancel(),
            self::backToPending(),
        ];
    }

    /**
     * Actions khusus untuk kasir (yang paling sering dipakai)
     * Simplified version
     */
    public static function cashierActions(): array
    {
        return [
            self::payAndPrint(),      // Aksi utama
            self::markAsPaid(),       // Alternatif tanpa print
            self::markAsUnpaid(),     // Untuk transaksi kredit
            self::cancel(),           // Batalkan
        ];
    }

    /**
     * Actions untuk manager/admin (lebih lengkap)
     */
    public static function adminActions(): array
    {
        return [
            self::payAndPrint(),
            self::markAsPaid(),
            self::markAsUnpaid(),
            self::cancel(),
            self::backToPending(),    // Extra untuk admin
        ];
    }
}

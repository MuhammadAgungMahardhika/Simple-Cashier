<?php

namespace App\Filament\Resources\TransactionResource\Actions;

use App\Models\Enums\TransactionStatusEnum;
use App\Models\Transaction;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Blade;

class PrintReceiptAction
{
    /**
     * Print action default (thermal)
     * Hanya bisa print jika status Paid
     */
    public static function make(): Action
    {
        return Action::make('print')
            ->label('Print')
            ->icon('heroicon-o-printer')
            ->color('success')
            ->visible(
                fn(Transaction $record) =>
                $record->status === TransactionStatusEnum::Paid->value
            )
            ->url(fn($record) => route('transaction.print.format', [
                'id' => $record->id,
                'format' => 'thermal'
            ]))
            ->openUrlInNewTab();
    }

    /**
     * Print dengan format tertentu
     * 
     * @param string $format Format cetak: thermal, a4, dotmatrix, lq310
     * @param array $allowedStatuses Status yang diizinkan untuk print (default: hanya Paid)
     * @return Action
     */
    public static function printWithFormat(
        string $format = "thermal",
        array $allowedStatuses = null
    ): Action {
        // Default: hanya Paid yang bisa print
        $allowedStatuses = $allowedStatuses ?? [TransactionStatusEnum::Paid->value];

        // Label yang lebih deskriptif
        $labels = [
            'thermal' => 'Thermal (58mm)',
            'a4' => 'A4',
            'dotmatrix' => 'Dot Matrix',
            'lq310' => 'LQ-310',
        ];

        $label = $labels[$format] ?? ucfirst($format);

        return Action::make("print_{$format}")
            ->label($label)
            ->icon('heroicon-o-printer')
            ->color('success')
            ->visible(
                fn(Transaction $record) =>
                in_array($record->status, $allowedStatuses)
            )
            ->url(fn($record) => route('transaction.print.format', [
                'id' => $record->id,
                'format' => $format
            ]))
            ->openUrlInNewTab();
    }

    /**
     * Print thermal - shorthand method
     */
    public static function thermal(array $allowedStatuses = null): Action
    {
        return self::printWithFormat('thermal', $allowedStatuses);
    }

    /**
     * Print A4 - shorthand method
     */
    public static function a4(array $allowedStatuses = null): Action
    {
        return self::printWithFormat('a4', $allowedStatuses);
    }

    /**
     * Print Dot Matrix - shorthand method
     */
    public static function dotmatrix(array $allowedStatuses = null): Action
    {
        return self::printWithFormat('dotmatrix', $allowedStatuses);
    }

    /**
     * Preview struk (tidak langsung print)
     * Bisa untuk semua status
     */
    public static function preview(string $format = 'thermal'): Action
    {
        $labels = [
            'thermal' => 'Preview Thermal',
            'a4' => 'Preview A4',
            'dotmatrix' => 'Preview Dot Matrix',
            'lq310' => 'Preview LQ-310',
        ];

        $label = $labels[$format] ?? 'Preview ' . ucfirst($format);

        return Action::make("preview_{$format}")
            ->label($label)
            ->icon('heroicon-o-eye')
            ->color('gray')
            // Preview bisa untuk semua status
            ->url(fn($record) => route('transaction.print.format', [
                'id' => $record->id,
                'format' => $format,
                'preview' => true  // Optional query param
            ]))
            ->openUrlInNewTab();
    }
}

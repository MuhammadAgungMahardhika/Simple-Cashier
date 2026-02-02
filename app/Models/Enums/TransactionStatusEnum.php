<?php

namespace App\Models\Enums;

enum TransactionStatusEnum: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Unpaid = 'unpaid';
    case Cancelled = 'cancelled';

    /**
     * Menyediakan label untuk setiap status pembayaran
     *
     * @return array
     */
    public static function labels(): array
    {
        return [
            self::Pending->value => 'Pending',
            self::Paid->value => 'Lunas',
            self::Unpaid->value => 'Belum Lunas',
            self::Cancelled->value => 'Dibatalkan',
        ];
    }

    /**
     * Mendapatkan label dari status pengiriman
     *
     * @return string
     */
    public function label(): string
    {
        return self::labels()[$this->value];
    }

    /**
     * Mendapatkan default case untuk enum
     *
     * @return self
     */
    public static function default(): self
    {
        return self::Pending;
    }

    public static function color($status): string
    {
        switch ($status) {
            case self::Pending->value:
                return 'warning';
                break;
            case self::Paid->value:
                return 'success';
                break;
            case self::Unpaid->value:
                return 'danger';
                break;
            case self::Cancelled->value:
                return '';
                break;
            default:
                return 'info';
        }
    }
}

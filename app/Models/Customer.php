<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Customer extends Model
{
    //
    use Blameable;
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->code)) {
                $model->code = self::generateCustomerCode();
            }
        });
    }

    private static function generateCustomerCode(): string
    {
        $date = Carbon::now()->format('dm');   // DDMM
        $year = Carbon::now()->format('y');    // YY

        // Ambil nomor urut terakhir
        $lastCustomer = self::orderBy('id', 'desc')->first();

        $lastNumber = 0;
        if ($lastCustomer && preg_match('/-(\d+)$/', $lastCustomer->code, $matches)) {
            $lastNumber = (int) $matches[1];
        }

        $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

        return "{$date}{$year}-{$nextNumber}";
    }
}

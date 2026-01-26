<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    //
    use Blameable;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->transaction_code)) {
                $model->transaction_code = self::generateCode();
            }
        });
    }

    private static function generateCode(): string
    {
        $date = now()->format('Ymd');
        $last = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $number = $last
            ? ((int) substr($last->transaction_code, -4)) + 1
            : 1;

        return 'TRX-' . $date . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function services()
    {
        return $this->belongsToMany(Service::class)->withPivot('quantity', 'price');
    }
    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }
}

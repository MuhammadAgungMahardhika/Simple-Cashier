<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionDetail extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionDetailFactory> */
    use HasFactory;


    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
    public function getSubtotalAttribute()
    {
        return $this->quantity * $this->price;
    }
    public function getTotalAttribute()
    {
        return $this->transaction->total_after_discount;
    }
}

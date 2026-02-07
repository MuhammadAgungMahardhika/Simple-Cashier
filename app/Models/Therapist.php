<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Therapist extends Model
{
    /** @use HasFactory<\Database\Factories\TherapistFactory> */
    use HasFactory;

    use Blameable;

    public function transactionDetails()
    {
        return $this->hasMany(TransactionDetail::class);
    }
}

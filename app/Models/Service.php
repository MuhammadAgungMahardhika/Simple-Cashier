<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceFactory> */
    use HasFactory;
    use Blameable;
    public function package()
    {
        return $this->belongsToMany(Package::class, 'package_details');
    }
    public function packageDetails()
    {
        return $this->hasMany(PackageDetail::class);
    }
    public function serviceGroup()
    {
        return $this->belongsTo(ServiceGroup::class);
    }
    public function transactionDetails()
    {
        return $this->hasMany(TransactionDetail::class);
    }
}

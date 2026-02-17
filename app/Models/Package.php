<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    /** @use HasFactory<\Database\Factories\PackageFactory> */
    use HasFactory;
    use Blameable;
    protected $with = ['services'];
    public function packageDetails()
    {
        return $this->hasMany(PackageDetail::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'package_details');
    }

    public function getTotalPackagePriceAttribute()
    {
        return $this->services->sum('package_price');
    }

    public function getTotalMemberPackagePriceAttribute()
    {
        return $this->services->sum('member_package_price');
    }
}

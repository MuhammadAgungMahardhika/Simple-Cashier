<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceGroup extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceGroupFactory> */
    use HasFactory;
    use Blameable;

    public function services()
    {
        return $this->hasMany(Service::class);
    }
}

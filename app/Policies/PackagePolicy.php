<?php

namespace App\Policies;

use App\Models\Package;
use App\Models\User;
use App\Traits\HasPolicyAuthorization;
use Illuminate\Auth\Access\Response;

class PackagePolicy
{
    use HasPolicyAuthorization;
}

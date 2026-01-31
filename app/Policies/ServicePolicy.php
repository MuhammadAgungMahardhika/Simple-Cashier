<?php

namespace App\Policies;

use App\Models\Services;
use App\Models\User;
use App\Traits\HasPolicyAuthorization;
use Illuminate\Auth\Access\Response;

class ServicePolicy
{
    use HasPolicyAuthorization;
}

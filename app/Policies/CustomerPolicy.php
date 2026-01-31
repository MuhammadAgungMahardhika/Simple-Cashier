<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use App\Traits\HasPolicyAuthorization;
use Illuminate\Auth\Access\Response;

class CustomerPolicy
{
    use HasPolicyAuthorization;
}

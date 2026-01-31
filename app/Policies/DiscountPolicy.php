<?php

namespace App\Policies;

use App\Models\Discount;
use App\Models\User;
use App\Traits\HasPolicyAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Testing\Fluent\Concerns\Has;

class DiscountPolicy
{
    use HasPolicyAuthorization;
}

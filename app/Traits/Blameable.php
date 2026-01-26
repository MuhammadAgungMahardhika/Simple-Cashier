<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait Blameable
{
    protected static function bootBlameable()
    {
        // Event saat model pertama kali dibuat
        static::creating(function ($model) {
            if (Auth::check()) { // Pastikan ada user yang login
                $model->created_by = Auth::user()->name ?? 'System';
            }
        });

        // Event saat model diperbarui
        static::updating(function ($model) {
            if (Auth::check()) { // Pastikan ada user yang login
                $model->updated_by =  Auth::user()->name ?? 'System';
            }
        });
    }
}

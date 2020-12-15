<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait HasAuthBy
{
    public static function bootHasAuthBy()
    {
        static::creating(function (Model $model) {
            $model->auth_by = Auth::id();
        });
    }
}

<?php

namespace App\Models\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

trait DeleteLogHasNull
{
    public static function bootDeleteLogHasNull()
    {
        static::created(function (Model $model) {
            ActivityLog::query()->where('event_id', '=', null)->delete();
        });
    }
}

<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

trait DeleteS3Path
{
    static $columns = ['path', 'svg', 'svg_saved'];

    public static function bootDeleteS3Path()
    {
        self::updating(function (Model $model) {
            foreach (self::$columns as $column) {
                if ($model->isDirty($column)) {
                    Storage::disk('s3')->delete($model->getOriginal($column));
                }
            }
        });
        self::deleted(function (Model $model) {
            foreach (self::$columns as $column) {
                if ($model->$column) {
                    Storage::disk('s3')->delete($model->$column);
                }
            }
        });
    }
}

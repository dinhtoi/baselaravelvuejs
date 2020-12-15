<?php

namespace App\Models\Traits;

use App\Models\User;
use Illuminate\Support\Facades\DB;

trait LogMessageActivity
{
    public function getFieldName($properties, $relations)
    {
        $key = array_intersect(array_keys($relations), array_keys($properties));
        foreach ($key as $value) {
            if ($relations[$value] == 'users') {
                $properties[$value] = User::query()->find($properties[$value])->full_name;
            } else {
                $properties[$value] = DB::table($relations[$value])->where('id', '=', $properties[$value])
                    ->value('name');
            }
        }
        return $properties;
    }

    public function createMessageLog($activity, $eventName, $names, $options, $relations, $unLog = null)
    {
        $properties = $activity->properties->toArray();
        if ($unLog && count($unLog) > 0) {
            foreach ($unLog as $item) {
                if (array_key_exists('attributes', $properties) && array_key_exists($item, $properties['attributes'])) {
                    $properties['attributes'][$item] = null;
                }
                if (array_key_exists('old', $properties) && array_key_exists($item, $properties['old'])) {
                    $properties['old'][$item] = null;
                }
            }
        }
        if (count($properties['attributes']) == 0 && count($properties['old']) == 0) {
            activity()->disableLogging();
        } else {
            if ($eventName == 'updated') {
                $properties['old'] = collect($properties['old'])
                    ->only(array_keys($properties['attributes']))
                    ->all();
            }
            $messages = '';
            $messagesOld = '';
            if (array_key_exists('attributes', $properties)) {
                if ($relations) {
                    $properties['attributes'] = $this->getFieldName($properties['attributes'], $relations);
                }
                foreach ($names as $key => $value) {
                    if (array_key_exists($key, $properties['attributes']) && isset($properties['attributes'][$key])) {
                        if (array_key_exists($key, $options)) {
                            $properties['attributes'][$key] = $options[$key][$properties['attributes'][$key]];
                        }
                        $messages = $messages . "$value : ". $properties['attributes'][$key] . "<br>";
                    }
                }
            }
            if (array_key_exists('old', $properties)) {
                if ($relations) {
                    $properties['old'] = $this->getFieldName($properties['old'], $relations);
                }
                foreach ($names as $key => $value) {
                    if (array_key_exists($key, $properties['old']) && isset($properties['old'][$key])) {
                        if (array_key_exists($key, $options)) {
                            $properties['old'][$key] = $options[$key][$properties['old'][$key]];
                        }
                        $messagesOld = $messagesOld . "$value : ". $properties['old'][$key] . "<br>";
                    }
                }
            }
            if ($eventName == 'deleted') {
                $activity->message_old = $messages;
            } else {
                $activity->message_att = $messages;
                $activity->message_old = $messagesOld;
            }
        }
    }
}

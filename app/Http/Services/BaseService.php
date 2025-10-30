<?php

namespace App\Http\Services;

use Illuminate\Database\Eloquent\Model;
use App\Consts;
use App\Utils;

class BaseService {

    public function saveData($instance, array $data)
    {
        foreach ($instance->getFillable() as $key => $field) {
            if (array_key_exists($field, $data)) {
                $instance->{$field} = $data[$field];
            }
        }
        if ($instance->isDirty()) {
            $instance->save();
        }
        return $instance;
    }
}

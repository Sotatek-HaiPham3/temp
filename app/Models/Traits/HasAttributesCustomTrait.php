<?php

namespace App\Models\Traits;

trait HasAttributesCustomTrait {

    protected static $withoutAppends = false;

    protected function getArrayableAppends()
    {
        if (static::$withoutAppends) {
            return [];
        }
        return parent::getArrayableAppends();
    }

    /**
     * Only can call static before call query builder
     */
    public static function withoutAppends()
    {
        static::$withoutAppends = true;
        return (new static);
    }
}

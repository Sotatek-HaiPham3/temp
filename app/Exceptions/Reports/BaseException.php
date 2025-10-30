<?php 

namespace App\Exceptions\Reports;

use Exception;

class BaseException extends Exception {

    protected $key;
    protected $message;

    public function __construct($key, $message = null)
    {
        $this->key = $key;
        $this->message = $message;

        parent::__construct(self::message());
    }

    public function key()
    {
        return $this->key;
    }

    private function message()
    {
        return $this->message ?? __($this->key);
    }
}

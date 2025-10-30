<?php

namespace App\Exceptions\Reports;

class FileUploadException extends BaseException {

    public function __construct()
    {
        parent::__construct('exceptions.fileupload_failed');
    }
}

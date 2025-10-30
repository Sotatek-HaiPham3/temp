<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileUpload extends Model {

    protected $fillable = [
        'user_id',
        'file_path',
        'is_used',
    ];
}

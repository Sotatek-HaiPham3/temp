<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Consts;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommunityInvitation extends Model
{
    protected $table = 'community_invitations';

    use SoftDeletes;

    protected $fillable = [
        'community_id',
        'sender_id',
        'receiver_id',
        'status', // created, accepted
        'description'
    ];
}

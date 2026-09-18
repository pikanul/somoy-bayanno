<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    /** @var array<int, string> */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message_type',
        'message',
        'website_url',
        'status',
        'ip_address',
        'user_agent',
    ];
}

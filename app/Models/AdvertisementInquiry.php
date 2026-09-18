<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdvertisementInquiry extends Model
{
    /** @var array<int, string> */
    protected $fillable = [
        'name',
        'company_name',
        'phone',
        'email',
        'advertisement_type',
        'placement',
        'budget',
        'starts_on',
        'ends_on',
        'landing_page_url',
        'message',
        'ip_address',
        'user_agent',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }
}

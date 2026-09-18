<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareerApplication extends Model
{
    /** @var array<int, string> */
    protected $fillable = [
        'career_vacancy_id',
        'full_name',
        'email',
        'phone',
        'position',
        'location',
        'cover_letter',
        'cv_path',
        'portfolio_url',
        'linkedin_url',
        'status',
        'ip_address',
        'user_agent',
    ];

    public function vacancy()
    {
        return $this->belongsTo(CareerVacancy::class, 'career_vacancy_id');
    }
}

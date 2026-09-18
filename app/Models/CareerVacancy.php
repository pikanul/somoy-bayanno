<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareerVacancy extends Model
{
    /** @var array<int, string> */
    protected $fillable = [
        'title',
        'slug',
        'department',
        'location',
        'employment_type',
        'status',
        'summary',
        'responsibilities',
        'requirements',
        'qualifications',
        'experience',
        'skills',
        'salary_benefits',
        'application_instructions',
        'application_email',
        'published_at',
        'application_deadline',
        'sort_order',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'application_deadline' => 'date',
        ];
    }

    public function applications()
    {
        return $this->hasMany(CareerApplication::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open'
            && (! $this->published_at || $this->published_at->isPast())
            && (! $this->application_deadline || $this->application_deadline->endOfDay()->isFuture());
    }
}

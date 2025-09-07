<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Career extends Model
{

    use HasFactory;
    protected $table = 'careers';

    protected $fillable = [
        'user_id',
        'title',
        'company',
        'description',
        'skills_used',
        'fit_category',
        'recommended_jobs',
        'analysis_notes',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'skills_used' => 'array',
        'recommended_jobs' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

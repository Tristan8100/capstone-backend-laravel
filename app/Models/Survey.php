<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    protected $table = 'surveys';

    protected $fillable = [
        'title',
        'description',
        'course_id',
        'status',
        'limits',
    ];

    protected $casts = [
        'limits' => 'array', // auto json_encode / json_decode
    ];

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function responses()
    {
        return $this->hasMany(Response::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    // Helpers thing
    public function isLimitedToCourse(string $courseId): bool
    {
        return isset($this->limits['courses']) &&
               in_array($courseId, $this->limits['courses']);
    }

    public function isLimitedToInstitute(string $instituteId): bool
    {
        return isset($this->limits['institutes']) &&
               in_array($instituteId, $this->limits['institutes']);
    }
}

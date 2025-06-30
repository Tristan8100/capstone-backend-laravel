<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Course extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'name', 'full_name', 'institute_id'];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}

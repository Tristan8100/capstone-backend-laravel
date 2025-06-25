<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlumniList extends Model
{
    use HasFactory;

    protected $table = 'alumni_list';

    protected $fillable = [
        'student_id',
        'first_name',
        'middle_name',
        'last_name',
        'course',
        'batch',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Institute extends Model
{
    use HasFactory;

    protected $table = 'institutes';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'name', 'description', 'image_path'];

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}

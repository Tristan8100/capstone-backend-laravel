<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnouncementLike extends Model
{

    protected $table = 'announcement_likes';

    protected $fillable = ['user_id', 'announcement_id'];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function announcement()
    {
        return $this->belongsTo(Announcement::class);
    }
}

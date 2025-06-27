<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $table = 'announcements';

    protected $fillable = ['admin_id', 'title', 'content', 'date'];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function images()
    {
        return $this->hasMany(AnnouncementImage::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}

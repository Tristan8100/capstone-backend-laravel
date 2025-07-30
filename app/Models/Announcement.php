<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

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

    public function likes()
    {
        return $this->hasMany(AnnouncementLike::class);
    }

    public function isLikedByUser($userId = null)
    {
        $userId = $userId ?? Auth::id();
        return $this->likes()->where('user_id', $userId)->exists();
    }
}

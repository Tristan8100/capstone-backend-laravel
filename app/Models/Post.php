<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $table = 'posts';

    protected $fillable = [
        'user_id', 'title', 'content', 'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(PostComment::class);
    }

    public function images()
    {
        return $this->hasMany(PostImage::class);
    }

    public function postLikes()
    {
        return $this->hasMany(PostLike::class);
    }

    // Quick access to likers
    public function likers()
    {
        return $this->belongsToMany(User::class, 'post_likes', 'post_id', 'user_id')
                ->withTimestamps();
    }

    // Check if user liked (optimized)
    public function isLikedByUser($userId)
    {
        return $this->postLikes()
                ->where('user_id', $userId)
                ->exists();
    }
}

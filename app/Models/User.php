<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
     protected $fillable = [
        'id',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'password',
        'course_id',
        'batch',
        'qr_code_path',
        'profile_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    public function getCourseNameAttribute()
    {
        return $this->course?->name;
    }

    //ANNOUNCEMENT MODULE
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    //POST MODULE
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    // Like
    public function postLikes()
    {
        return $this->hasMany(PostLike::class);
    }

    public function likedPosts()
    {
        return $this->belongsToMany(Post::class, 'post_likes', 'user_id', 'post_id')
                ->withTimestamps();
    }

    public function postComments()
    {
        return $this->hasMany(PostComment::class);
    }

    //COURSE MODULE
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    //SURVEY MODULE
    public function responses()
    {
        return $this->hasMany(Response::class);
    }

    // CAREER TRACKING
    public function careers()
    {
        return $this->hasMany(Career::class);
    }

}

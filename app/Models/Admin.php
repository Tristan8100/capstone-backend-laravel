<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
class Admin extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'admins';

    protected $guard = 'admin';

    protected $fillable = [
        'name', 'email', 'password', 'profile_path',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    public function getFullNameAttribute()
    {
        return $this->name;
    }

    public function announcements()
    {
        return $this->hasMany(Announcement::class);
    }
}
